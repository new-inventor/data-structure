<?php
/**
 * Project: property-bag
 * User: george
 * Date: 09.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Metadata\Configuration;
use NewInventor\DataStructure\Metadata\Loader;
use NewInventor\DataStructure\Metadata\Metadata;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class RecursiveConstructor
{
    /** @var Loader */
    protected $metadataLoader;
    /** @var array */
    protected $errors = [];
    /** @var bool */
    protected $failOnFirstError = true;
    /** @var ConfigurationInterface */
    protected $configuration;
    
    /**
     * DataStructureRecursiveConstructor constructor.
     *
     * @param Loader                 $metadataLoader
     * @param ConfigurationInterface $configuration
     */
    public function __construct(Loader $metadataLoader, ConfigurationInterface $configuration)
    {
        $this->metadataLoader = $metadataLoader;
        $this->configuration = $configuration;
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
     * @param string                      $className
     * @param array                       $properties
     * @param ConfigurationInterface|null $configuration
     * @param string                      $group
     *
     * @return mixed
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @internal param ConfigurationInterface $config
     */
    public function construct(
        string $className,
        array $properties = [],
        string $group = Configuration::DEFAULT_GROUP_NAME
    ) {
        $metadata = $this->metadataLoader->loadMetadataFor($className, $this->configuration);
        $transformedProperties = $this->transform($properties, $metadata, $group);
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
    
    protected function transform(array $properties, Metadata $metadata, $group): array
    {
        $transformer = $metadata->getTransformer($group);
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
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
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
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    protected function constructConcreteNested($propertyValue, $propertyName, $config)
    {
        if (isset($nestedConfig['metadata'])) {
            $metadataLoader = new Loader($config['metadata']['path'], $config['metadata']['baseNamespace']);
            $configuration = $this->configuration;
            if ($config['metadata']['configuration'] !== null) {
                $class = $config['metadata']['configuration'];
                $configuration = new $class();
            }
            $nestedConstructor = new self($metadataLoader, $configuration);
        } else {
            $nestedConstructor = new self($this->metadataLoader, $this->configuration);
        }
        $nestedConstructor->setFailOnFirstError($this->failOnFirstError);
        
        $result = $nestedConstructor->construct($config['class'], $propertyValue);
        $this->errors = array_merge($this->errors, [$propertyName => $nestedConstructor->getErrors()]);
        
        return $result;
    }
}