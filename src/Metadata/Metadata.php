<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\StructureTransformerInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Metadata implements MetadataInterface
{
    /** @var string */
    public $namespace;
    /** @var string */
    public $className;
    /** @var string */
    public $fullClassName;
    /** @var string[] */
    public $properties = [];
    /** @var StructureTransformerInterface[] */
    public $transformers = [];
    /** @var array[] */
    public $nested = [];
    /** @var array */
    public $configArray = [];
    /** @var ClassMetadata */
    public $validationMetadata;
    
    /**
     * Metadata constructor.
     *
     * @param string              $class
     */
    public function __construct(string $class)
    {
        $this->fullClassName = $class;
        $lastDelimiterPos = strrpos($class, '\\');
        if ($lastDelimiterPos === false) {
            $lastDelimiterPos = null;
        }
        $this->className = substr($class, $lastDelimiterPos ? $lastDelimiterPos + 1 : 0);
        $this->namespace = trim(substr($class, 0, $lastDelimiterPos), "\t\n\r\0\x0B\\/");
        $this->validationMetadata = new ClassMetadata($class);
    }
    
    /**
     * @return string
     */
    public function getFullClassName(): string
    {
        return $this->fullClassName;
    }
    
    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
    
    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    /**
     * @return array
     */
    public function getConfigArray(): array
    {
        return $this->configArray;
    }
    
    /**
     * @return ClassMetadata
     */
    public function getValidationMetadata(): ClassMetadata
    {
        return $this->validationMetadata;
    }
    
    /**
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME): ?StructureTransformerInterface
    {
        return $this->transformers[$group];
    }
}