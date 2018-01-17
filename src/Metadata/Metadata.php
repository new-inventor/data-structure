<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\StructureTransformerInterface;
use NewInventor\TypeChecker\TypeChecker;

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
    
    /**
     * Metadata constructor.
     *
     * @param $obj
     *
     * @throws \NewInventor\TypeChecker\Exception\TypeException
     */
    public function __construct($obj)
    {
        TypeChecker::check($obj)->tstring()->tobject()->fail();
        $class = is_object($obj) ? get_class($obj) : $obj;
        $this->fullClassName = trim($class, "\t\n\r\0\x0B\\/");
        $lastDelimiterPos = strrpos($class, '\\');
        if ($lastDelimiterPos === false) {
            $lastDelimiterPos = null;
        }
        $this->className = trim(substr($class, $lastDelimiterPos ? $lastDelimiterPos + 1 : 0), "\t\n\r\0\x0B\\/");
        $this->namespace = trim(substr($class, 0, $lastDelimiterPos), "\t\n\r\0\x0B\\/");
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
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME): ?StructureTransformerInterface
    {
        return $this->transformers[$group];
    }
}