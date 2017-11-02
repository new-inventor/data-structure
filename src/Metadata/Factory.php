<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\MetadataLoaderInterface;
use Psr\Cache\CacheItemPoolInterface;

class Factory
{
    /** @var CacheItemPoolInterface */
    protected $cache;
    /** @var MetadataLoaderInterface */
    protected $loader;
    
    /**
     * Factory constructor.
     *
     * @param MetadataLoaderInterface $loader
     * @param CacheItemPoolInterface  $metadataCache
     */
    public function __construct(
        MetadataLoaderInterface $loader,
        CacheItemPoolInterface $metadataCache = null
    ) {
        $this->cache = $metadataCache;
        $this->loader = $loader;
    }
    
    /**
     * @param $obj
     *
     * @return MetadataInterface
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \InvalidArgumentException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \NewInventor\TypeChecker\Exception\TypeException
     */
    public function getMetadataFor($obj): MetadataInterface
    {
        $class = is_object($obj) ? get_class($obj) : $obj;
    
        if ($this->cache !== null) {
            $key = $this->getCacheKey($class);
            $item = $this->cache->getItem($key);
            if (!$item->isHit()) {
                $metadata = $this->constructMetadata($class);
                $item->set($metadata);
                $this->cache->save($item);
            }
            
            return $item->get();
        }
    
        return $this->constructMetadata($class);
    }
    
    protected function constructMetadata($class)
    {
        $metadata = new Metadata($class);
        $this->loader->load($metadata);
        
        return $metadata;
    }
    
    protected function getCacheKey(string $class)
    {
        return preg_replace('/[\\\\{}()\\/@]+/', '_', $class);
    }
}