<?php
/**
 * Project: property-bag
 * User: george
 * Date: 08.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\Transformers\TransformerContainerInterface;
use NewInventor\Transformers\TransformerInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_GROUP_NAME = 'default';
    
    /**
     * @return TreeBuilder
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder
            ->root('metadata')
            ->append($this->getNamespaceNode())
            ->append($this->getValidationNode())
            ->append($this->getPropertiesNode());
        
        return $builder;
    }
    
    /**
     * @return ScalarNodeDefinition
     */
    protected function getNamespaceNode(): ScalarNodeDefinition
    {
        return (new ScalarNodeDefinition('namespace'))->defaultValue('')->example('Some\Namespace\String');
    }
    
    protected function getValidationNode(): ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition('validation'))
            ->children()
            ->arrayNode('constraints')->variablePrototype()->end()->end()
            ->arrayNode('getters')->arrayPrototype()->variablePrototype()->end()->end()->end()
            ->arrayNode('properties')->arrayPrototype()->variablePrototype()->end()->end()->end()
            ->end();
    }
    
    /**
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     * @throws \RuntimeException
     */
    protected function getPropertiesNode(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder();
        $node = $builder->root('properties');
        
        $node
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->beforeNormalization()
            ->ifString()
            ->then(
                function ($v) {
                    return ['transformers' => $v];
                }
            )
            ->end()
            ->append($this->getDefaultNode())
            ->append($this->getNestedNode())
            ->append($this->getTransformersNode())
            ->append($this->getPropertyValidationNode())
            ->end();
        return $node;
    }
    
    protected function getDefaultNode()
    {
        return (new VariableNodeDefinition(self::DEFAULT_GROUP_NAME))
            ->beforeNormalization()
            ->ifTrue(\Closure::fromCallable([$this, 'checkCustomParameters']))
            ->then(\Closure::fromCallable([$this, 'normalizeDefault']))
            ->end()
            ->defaultNull()
            ->example(1);
    }
    
    protected function getNestedNode()
    {
        return (new ArrayNodeDefinition('nested'))
            ->beforeNormalization()
            ->ifString()
            ->then(
                function ($v) {
                    return ['class' => $v];
                }
            )
            ->end()
            ->children()
            ->arrayNode('metadata')
            ->children()
            ->scalarNode('path')->isRequired()->end()
            ->scalarNode('baseNamespace')->defaultValue('')->end()
            ->scalarNode('factory')->defaultNull()->end()
            ->end()
            ->end()
            ->scalarNode('class')->isRequired()->end()
            ->booleanNode('array')->defaultFalse()->end()
            ->end();
    }
    
    protected function getTransformersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('transformers');
        
        $node
            ->beforeNormalization()
            ->ifString()
            ->then(
                function ($v) {
                    return [[$v => [['groups' => self::DEFAULT_GROUP_NAME]]]];
                }
            )
            ->end()
            ->arrayPrototype()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'classNotExists']))
            ->then(\Closure::fromCallable([$this, 'prepareShortcutTransformer']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'classExists']))
            ->then(\Closure::fromCallable([$this, 'checkImplementation']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'isTransformerContainer']))
            ->then(\Closure::fromCallable([$this, 'getContainerNode']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'isTransformer']))
            ->then(\Closure::fromCallable([$this, 'getTransformerNode']))
            ->end()
            ->variablePrototype()
            ->beforeNormalization()
            ->ifTrue(\Closure::fromCallable([$this, 'checkCustomParameters']))
            ->then(\Closure::fromCallable([$this, 'normalizeCustomParameters']))
            ->end()
            ->end();
        
        return $node;
    }
    
    protected function normalizeDefault($v)
    {
        if ($v === null) {
            return null;
        }
        if (is_callable($v)) {
            return call_user_func($v);
        }
        if (is_scalar($v)) {
            return $v;
        }
        foreach ($v as $key => $parameter) {
            if (is_callable($parameter)) {
                return call_user_func($parameter);
                continue;
            }
            if (is_array($parameter) && count($parameter) === 1) {
                if (isset($parameter['const'])) {
                    if (is_array($parameter['const'])) {
                        [$className, $constantName] = $parameter['const'];
                        
                        return constant("$className::$constantName");
                    }
                    if (is_string($parameter['const'])) {
                        return constant($parameter['const']);
                    }
                }
                if (isset($parameter['static'])) {
                    [$className, $staticName] = $parameter['static'];
                    
                    return $className::$$staticName;
                }
            }
        }
        
        return $v;
    }
    
    protected function checkCustomParameters($v)
    {
        if ($v === null) {
            return true;
        }
        if (is_callable($v)) {
            return true;
        }
        if (is_scalar($v)) {
            return true;
        }
        foreach ($v as $parameter) {
            if (is_callable($parameter)) {
                return true;
            }
            if (is_array($parameter) && count($parameter) === 1) {
                if (
                    isset($parameter['const']) ||
                    isset($parameter['static']) ||
                    isset($parameter['groups'])
                ) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    protected function normalizeCustomParameters($v)
    {
        if ($v === null) {
            return [];
        }
        if (is_callable($v)) {
            return call_user_func($v);
        }
        if (is_scalar($v)) {
            return [$v];
        }
        $groupSet = false;
        foreach ($v as $key => $parameter) {
            if (is_callable($parameter)) {
                $v[$key] = call_user_func($parameter);
                continue;
            }
            if (is_array($parameter) && count($parameter) === 1) {
                if (isset($parameter['const'])) {
                    [$className, $constantName] = $parameter['const'];
                    $v[$key] = constant("$className::$constantName");
                } else if (isset($parameter['static'])) {
                    [$className, $staticName] = $parameter['static'];
                    $v[$key] = $className::$$staticName;
                } else if (isset($parameter['groups'])) {
                    $groupSet = true;
                    if ($parameter['groups'] === null) {
                        $v[$key]['groups'] = [self::DEFAULT_GROUP_NAME];
                    } else if (is_string($parameter['groups'])) {
                        $v[$key]['groups'] = [$parameter['groups']];
                    } else if (!is_array($parameter['groups'])) {
                        throw new \InvalidArgumentException('groups must be string or array');
                    }
                }
            }
        }
        if (!$groupSet) {
            $v[]['groups'] = [self::DEFAULT_GROUP_NAME];
        }
        
        return $v;
    }
    
    protected function getPropertyValidationNode()
    {
        return (new ArrayNodeDefinition('validation'))->variablePrototype()->end();
    }
    
    protected function evaluateInnerTransformer($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        
        $node
            ->arrayPrototype()
            ->variablePrototype()
            ->beforeNormalization()
            ->ifTrue(\Closure::fromCallable([$this, 'checkCustomParameters']))
            ->then(\Closure::fromCallable([$this, 'normalizeCustomParameters']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'classNotExists']))
            ->then(\Closure::fromCallable([$this, 'prepareShortcutTransformer']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'classExists']))
            ->then(\Closure::fromCallable([$this, 'checkImplementation']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'isTransformerContainer']))
            ->then(\Closure::fromCallable([$this, 'getContainerNode']))
            ->end()
            ->validate()
            ->ifTrue(\Closure::fromCallable([$this, 'isTransformer']))
            ->then(\Closure::fromCallable([$this, 'getInnerTransformerNode']))
            ->end()
            ->end()
            ->end();
        
        return $node;
    }
    
    protected function makeTransformer($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        
        $node
            ->validate()
            ->ifTrue(
                function ($v) {
                    return array_values($v)[0] === null;
                }
            )
            ->then(
                Function ($v) {
                    return [array_keys($v)[0] => [['groups' => ['default']]]];
                }
            )
            ->end()
            ->validate()
            ->ifTrue(
                function ($v) {
                    return is_scalar(array_values($v)[0]);
                }
            )
            ->then(
                Function ($v) {
                    $transformerName = array_keys($v)[0];
                    return [$transformerName => [$v[$transformerName]]];
                }
            )
            ->end()
            ->variablePrototype()
            ->beforeNormalization()
            ->ifTrue(\Closure::fromCallable([$this, 'checkCustomParameters']))
            ->then(\Closure::fromCallable([$this, 'normalizeCustomParameters']))
            ->end()
            ->end()
            ->end();
        
        return $node;
    }
    
    protected function makeInnerTransformer($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        
        $node
            ->validate()
            ->ifTrue(
                function ($v) {
                    return array_values($v)[0] === null;
                }
            )
            ->then(
                Function ($v) {
                    return [array_keys($v)[0] => []];
                }
            )
            ->end()
            ->validate()
            ->ifTrue(
                function ($v) {
                    return is_scalar(array_values($v)[0]);
                }
            )
            ->then(
                Function ($v) {
                    $transformerName = array_keys($v)[0];
                    return [$transformerName => [$v[$transformerName]]];
                }
            )
            ->end()
            ->variablePrototype()
            ->beforeNormalization()
            ->ifTrue(\Closure::fromCallable([$this, 'checkCustomParameters']))
            ->then(\Closure::fromCallable([$this, 'normalizeCustomParameters']))
            ->end()
            ->end()
            ->end();
        
        return $node;
    }
    
    public function classNotExists($v)
    {
        $transformerName = array_keys($v)[0];
        if($transformerName === 'groups'){
            return false;
        }
        return !class_exists($transformerName);
    }
    
    public function prepareShortcutTransformer($v)
    {
        $validatorName = array_keys($v)[0];
        $validatorClass = 'NewInventor\Transformers\Transformer\\' . $validatorName;
        if (!class_exists($validatorClass)) {
            throw new \InvalidArgumentException("Class $validatorClass does not exist.");
        }
        $v[$validatorClass] = $v[$validatorName];
        unset($v[$validatorName]);
    
        return $v;
    }
    
    public function classExists($v)
    {
        $transformerName = array_keys($v)[0];
        if($transformerName === 'groups'){
            return false;
        }
        return class_exists(array_keys($v)[0]);
    }
    
    public function checkImplementation ($v) {
        $validatorName = array_keys($v)[0];
        if (
            !in_array(TransformerInterface::class, class_implements($validatorName), true) &&
            !in_array(TransformerContainerInterface::class, class_implements($validatorName), true)
        ) {
            throw new \InvalidArgumentException(
                "Class $validatorName does not implement " . TransformerInterface::class
            );
        }
        return $v;
    }
    
    public function isTransformerContainer($v)
    {
        $transformerName = array_keys($v)[0];
        if($transformerName === 'groups'){
            return false;
        }
        return in_array(TransformerContainerInterface::class, class_implements($transformerName), true);
    }
    
    public function getContainerNode($v)
    {
        return $this->evaluateInnerTransformer(array_keys($v)[0])->getNode(true)->finalize($v);
    }
    
    public function isTransformer($v)
    {
        $transformerName = array_keys($v)[0];
        if($transformerName === 'groups'){
            return false;
        }
        return in_array(TransformerInterface::class, class_implements($transformerName), true) &&
               !in_array(TransformerContainerInterface::class, class_implements($transformerName), true);
    }
    
    public function getTransformerNode($v)
    {
        return $this->makeTransformer(array_keys($v)[0])->getNode(true)->finalize($v);
    }
    
    public function getInnerTransformerNode($v)
    {
        return $this->makeInnerTransformer(array_keys($v)[0])->getNode(true)->finalize($v);
    }
}