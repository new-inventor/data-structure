<?php
/**
 * Project: data-structure
 * User: george
 * Date: 07.11.17
 */

namespace NewInventor\DataStructure\Exception;


class MetadataFileNotFoundException extends \InvalidArgumentException
{
    /**
     * MetadataFileNotFoundException constructor.
     */
    public function __construct($path, $metadataClassName)
    {
        parent::__construct("Path '$path' for class '{$metadataClassName}' does not exists.");
    }
}