<?php
/**
 * Project: data-structure
 * User: george
 * Date: 11.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\StructureTransformerInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

interface MetadataInterface
{
    /**
     * @return ClassMetadata
     */
    public function getValidationMetadata(): ClassMetadata;
    
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