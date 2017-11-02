<?php
/**
 * Project: property-bag
 * User: george
 * Date: 09.10.17
 */

namespace NewInventor\DataStructure\Loader;


use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\DataStructureInterface;
use NewInventor\DataStructure\Exception\LoadingNestedException;
use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\DataStructure\Metadata\Factory;
use NewInventor\DataStructure\Metadata\Metadata;
use NewInventor\TypeChecker\Exception\TypeException;
use NewInventor\TypeChecker\TypeChecker;
use Psr\Cache\InvalidArgumentException;

class RecursiveObjectLoader
{
    /** @var Factory */
    protected $metadataFactory;
    /** @var string */
    protected $group;
    
    /**
     * DataStructureRecursiveConstructor constructor.
     *
     * @param Factory $metadataFactory
     * @param string  $group
     */
    public function __construct(
        Factory $metadataFactory,
        string $group = Configuration::DEFAULT_GROUP_NAME
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->group = $group;
    }
    
    /**
     * @param DataStructureInterface $obj
     * @param array                  $properties
     *
     * @return array Errors
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \NewInventor\DataStructure\Exception\LoadingNestedException
     * @throws PropertyTransformationException
     * @throws PropertyInvalidTypeException
     * @throws InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws TypeException
     */
    public function load(DataStructureInterface $obj, array $properties): array
    {
        $objClass = get_class($obj);
        /** @var Metadata $metadata */
        $metadata = $this->metadataFactory->getMetadataFor($objClass);
        $transformer = $metadata->getTransformer($this->group);
        if ($transformer === null) {
            throw new \InvalidArgumentException("No transformers in group '{$this->group}' of class '{$objClass}'.");
        }
        $transformedProperties = $transformer->transform($properties);
        $errors = $transformer->getErrors();
        if (count($metadata->nested) !== 0) {
            $errors = array_merge($errors, $this->loadNested($metadata->nested, $transformedProperties));
        }
        foreach ($transformedProperties as $propertyName => $propertyValue) {
            $obj->set($propertyName, $propertyValue);
        }
        
        return $errors;
    }
    
    /**
     * @param array $nestedConfigs
     * @param array $properties
     *
     * @return array
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws LoadingNestedException
     * @throws PropertyTransformationException
     * @throws PropertyInvalidTypeException
     * @throws TypeException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    protected function loadNested(array $nestedConfigs, array &$properties): array
    {
        $errors = [];
        foreach ($nestedConfigs as $propertyName => $config) {
            if (!isset($properties[$propertyName])) {
                continue;
            }
            if ($config['array']) {
                foreach ($properties[$propertyName] as $key => $oneNestedProperties) {
                    if (($nestedException = $this->getNestedException($oneNestedProperties)) !== []) {
                        $errors[$propertyName][$key] = $nestedException;
                        continue;
                    }
                    [$obj, $nestedErrors] = $this->loadConcreteNested(
                        $oneNestedProperties,
                        $config
                    );
                    $properties[$propertyName][$key] = $obj;
                    if ($nestedErrors !== []) {
                        $errors[$propertyName][$key] = $nestedErrors;
                    }
                }
                continue;
            }
            if (($nestedException = $this->getNestedException($properties[$propertyName])) !== []) {
                $errors[$propertyName] = $nestedException;
                continue;
            }
            [$obj, $nestedErrors] = $this->loadConcreteNested(
                $properties[$propertyName],
                $config
            );
            $properties[$propertyName] = $obj;
            if ($nestedErrors !== []) {
                $errors[$propertyName] = $nestedErrors;
            }
        }
        
        return $errors;
    }
    
    protected function getNestedException($nested): array
    {
        return TypeChecker::check($nested)->tnull()->tarray()->result() ?
            [] :
            ['TYPE_EXCEPTION' => ['RECURSIVE_LOADER' => 'Nested must be array or null']];
    }
    
    /**
     * @param $propertyValue
     * @param $config
     *
     * @return mixed
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \NewInventor\DataStructure\Exception\LoadingNestedException
     * @throws PropertyTransformationException
     * @throws PropertyInvalidTypeException
     * @throws TypeException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    protected function loadConcreteNested($propertyValue, $config)
    {
        if ($propertyValue === null) {
            return [null, []];
        }
        $nestedConstructor = new self($this->metadataFactory, $this->group);
        
        $nestedClass = $config['class'];
        $obj = new $nestedClass();
        $errors = $nestedConstructor->load($obj, $propertyValue);
        
        return [$obj, $errors];
    }
}