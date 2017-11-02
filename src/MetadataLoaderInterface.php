<?php
/**
 * Project: data-structure
 * User: george
 * Date: 02.11.17
 */

namespace NewInventor\DataStructure;


interface MetadataLoaderInterface
{
    /**
     * @param mixed $metadata
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function load($metadata): void;
    
    /**
     * @return string
     */
    public function getPath(): string;
    
    /**
     * @return string
     */
    public function getBaseNamespace(): string;
}