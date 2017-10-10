<?php
/**
 * Project: property-bag
 * User: george
 * Date: 09.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Metadata\Loader;
use NewInventor\DataStructure\Metadata\Metadata;

//TODO mapping
class RecursiveConstructor
{
    /** @var Loader */
    protected $metadataLoader;
    /** @var array */
    protected $errors = [];
    /** @var bool */
    protected $failOnFirstError = true;
    
    /**
     * DataStructureRecursiveConstructor constructor.
     *
     * @param Loader $metadataLoader
     */
    public function __construct(Loader $metadataLoader)
    {
        $this->metadataLoader = $metadataLoader;
    }
    
    /**
     * @return bool
     */
    public function isFailOnFirstError(): bool
    {
        return $this->failOnFirstError;
    }
    
    /**
     * @param bool $failOnFirstError
     *
     * @return $this
     */
    public function setFailOnFirstError(bool $failOnFirstError)
    {
        $this->failOnFirstError = $failOnFirstError;
        
        return $this;
    }
    
    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * @param string $className
     * @param array  $properties
     *
     * @return mixed
     * @throws \LogicException
     */
    public function construct(string $className, array $properties = [])
    {
        $metadata = $this->metadataLoader->loadMetadataFor($className);
        $transformedProperties = $this->transform($properties, $metadata);
        $constructedNested = $this->constructNested($metadata->getNested(), $transformedProperties);
        $transformedProperties = array_merge($transformedProperties, $constructedNested);
        if (
            in_array(Loadable::class, class_implements($className), true) ||
            in_array(DataStructureInterface::class, class_implements($className), true)
        ) {
            /** @var Loadable $dataStructure */
            $dataStructure = new $className();
            $dataStructure->load($transformedProperties);
            
            return $dataStructure;
        }
        throw new \LogicException(
            'Data structure class must implement ' .
            Loadable::class .
            ' or ' .
            DataStructureInterface::class
        );
    }
    
    protected function transform(array $properties, Metadata $metadata): array
    {
        $transformer = $metadata->getTransformer('load');
        $transformer->setFailOnFirstError($this->failOnFirstError);
        $transformedProperties = $transformer->transform($properties);
        $this->errors = array_merge($this->errors, $transformer->getErrors());
        
        return $transformedProperties;
    }
    
    /**
     * @param array $nestedConfigs
     * @param array $properties
     *
     * @return array
     * @throws \LogicException
     */
    protected function constructNested(array $nestedConfigs, array $properties): array
    {
        $res = [];
        if (count($nestedConfigs) > 0) {
            foreach ($nestedConfigs as $propertyName => $config) {
                $res[$propertyName] = $this->constructConcreteNested(
                    $properties[$propertyName],
                    $propertyName,
                    $config
                );
            }
        }
        
        return $res;
    }
    
    /**
     * @param $propertyValue
     * @param $propertyName
     * @param $config
     *
     * @return mixed
     * @throws \LogicException
     */
    protected function constructConcreteNested($propertyValue, $propertyName, $config)
    {
        if (isset($nestedConfig['metadata'])) {
            $metadataLoader = new Loader($config['metadata']['path'], $config['metadata']['baseNamespace']);
            $nestedConstructor = new self($metadataLoader);
        } else {
            $nestedConstructor = new self($this->metadataLoader);
        }
        $nestedConstructor->setFailOnFirstError($this->failOnFirstError);
    
        $result = $nestedConstructor->construct($config['class'], $propertyValue);
        $this->errors = array_merge($this->errors, [$propertyName => $nestedConstructor->getErrors()]);
    
        return $result;
    }
}