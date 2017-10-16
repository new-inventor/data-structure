<?php
/**
 * Project: data-structure
 * User: george
 * Date: 11.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\StructureTransformerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface MetadataInterface
{
    /**
     * @return ValidatorInterface
     */
    public function getValidator(): ValidatorInterface;
    
    /**
     * @return string
     */
    public function getFullClassName(): string;
    
    /**
     * @return string
     */
    public function getClassName(): string;
    
    /**
     * @return string
     */
    public function getNamespace(): string;
    
    /**
     * @return array
     */
    public function getConfigArray(): array;
    
    /**
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME
    ): ?StructureTransformerInterface;
}