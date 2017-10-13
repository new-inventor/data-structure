<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\DataStructure\Metadata\Configuration;
use NewInventor\DataStructure\Metadata\Factory;
use NewInventor\TypeChecker\Exception\TypeException;
use Psr\Cache\InvalidArgumentException;

class Loader
{
    /** @var array */
    protected $errors = [];
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
        $transformer = $this->metadataFactory
            ->getMetadata($objClass)
            ->getTransformer($this->group);
        $transformedProperties = $transformer->transform($properties);
        foreach ($transformedProperties as $propertyName => $propertyValue) {
            $obj->set($propertyName, $propertyValue);
        }
        
        return $transformer->getErrors();
    }
}