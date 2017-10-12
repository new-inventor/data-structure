<?php
/**
 * Project: property-bag
 * User: george
 * Date: 09.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Exception\LoadingNestedException;
use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\DataStructure\Metadata\Configuration;
use NewInventor\DataStructure\Metadata\Factory;
use NewInventor\DataStructure\Metadata\Metadata;
use NewInventor\TypeChecker\Exception\TypeException;
use NewInventor\TypeChecker\TypeChecker;
use Psr\Cache\InvalidArgumentException;

class RecursiveLoader
{
    /** @var bool */
    protected $mute = true;
    /** @var Factory */
    protected $metadataFactory;
    /** @var string */
    protected $group;
    
    /**
     * DataStructureRecursiveConstructor constructor.
     *
     * @param Factory $metadataFactory
     * @param string  $group
     * @param bool    $mute
     */
    public function __construct(
        Factory $metadataFactory,
        string $group = Configuration::DEFAULT_GROUP_NAME,
        bool $mute = false
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->group = $group;
        $this->mute = $mute;
    }
    
    /**
     * @param DataStructureInterface $obj
     * @param array                  $properties
     *
     * @return array Errors
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
        $metadata = $this->metadataFactory->getMetadata($objClass);
        $transformer = $metadata->getTransformer($this->group);
        $transformedProperties = $transformer->transform($properties, $this->mute);
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
    
    protected function getNestedException($nested)
    {
        try {
            TypeChecker::check($nested)->tnull()->tarray()->fail();
        } catch (TypeException $e) {
            if (!$this->mute) {
                throw $e;
            }
            
            return ['TYPE_EXCEPTION' => $e->getMessage()];
        }
        
        return [];
    }
    
    /**
     * @param $propertyValue
     * @param $config
     *
     * @return mixed
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
        $nestedConstructor = null;
        if (isset($config['metadata'])) {
            $metadataConfig = $config['metadata'];
            $factory = $this->generateFactory($metadataConfig['factory'] ?? self::class, $metadataConfig);
            $nestedConstructor = new self($factory, $this->group, $this->mute);
        } else {
            $nestedConstructor = new self($this->metadataFactory, $this->group, $this->mute);
        }
        
        $nestedClass = $config['class'];
        $obj = new $nestedClass();
        $errors = $nestedConstructor->load($obj, $propertyValue);
        
        return [$obj, $errors];
    }
    
    protected function generateFactory(string $factoryClass, array $metadataConfig)
    {
        return new $factoryClass(
            $metadataConfig['path'],
            $metadataConfig['baseNamespace'],
            $this->metadataFactory->getMetadataCache(),
            $this->metadataFactory->getValidationCache()
        );
    }
}