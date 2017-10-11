<?php
/**
 * Project: data-structure
 * User: george
 * Date: 11.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface MetadataInterface
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
     * @param string                 $file
     * @param ConfigurationInterface $configuration
     *
     * @return $this
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function loadConfig(string $file, ConfigurationInterface $configuration);
    
    /**
     * @param string $metaName
     *
     * @return mixed
     */
    public function get(string $metaName);
    
    /**
     * @param CacheInterface $cacheDriver
     *
     * @return ValidatorInterface
     */
    public function getValidator(CacheInterface $cacheDriver = null): ValidatorInterface;
}