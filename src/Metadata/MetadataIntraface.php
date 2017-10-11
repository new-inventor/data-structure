<?php
/**
 * Project: data-structure
 * User: george
 * Date: 11.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\StructureTransformerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface MetadataIntraface
{
    /**
     * @param string $file
     *
     * @return mixed
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public static function getConfig(string $file);
    
    /**
     * @param string $file
     *
     * @return mixed
     */
    public static function getClassNameFromFile(string $file);
    
    /**
     * @param string $file
     *
     * @return $this
     */
    public function loadConfig(string $file);
    
    /**
     * @return string
     */
    public function getFullClassName(): string;
    
    /**
     * @return string
     */
    public function getNamespace(): string;
    
    /**
     * @return string
     */
    public function getClassName(): string;
    
    /**
     * @return ValidatorInterface
     */
    public function getValidator(): ValidatorInterface;
    
    /**
     * @return string[]
     */
    public function getProperties(): array;
    
    /**
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME): StructureTransformerInterface;
    
    /**
     * @return array
     */
    public function getNested(): array;
}