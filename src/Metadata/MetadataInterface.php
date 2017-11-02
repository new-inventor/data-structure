<?php
/**
 * Project: data-structure
 * User: george
 * Date: 11.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\StructureTransformerInterface;

interface MetadataInterface
{
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
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME
    ): ?StructureTransformerInterface;
}