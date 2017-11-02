<?php

namespace NewInventor\DataStructure\Helper;


/**
 * Project: data-structure
 * User: george
 * Date: 02.11.17
 */

class ObjectHelper
{
    private static $instance;
    
    protected function __construct()
    {
    
    }
    
    public static function make(): ObjectHelper
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function getFilePathForClass(
        string $baseDir,
        string $pathForClass,
        string $extension = 'php'
    ): string {
        return sprintf('%s%s%s.%s', $baseDir, DIRECTORY_SEPARATOR, $pathForClass, $extension);
    }
    
    public function truncateBaseNamespace(string $className, string $baseNamespace): string
    {
        return str_replace($baseNamespace, '', $className);
    }
    
    public function replaceNamespaceDelimiter(string $className, string $replacer): string
    {
        return str_replace('\\', $replacer, $className);
    }
    
    public function normalizeNamespace($namespace): string
    {
        return trim($namespace, "\t\n\r\0\x0B\\/");
    }
}