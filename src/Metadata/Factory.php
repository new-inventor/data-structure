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
    protected $metadataCache;
    /** @var MetadataLoaderInterface */
    private $loader;
    
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
        $this->metadataCache = $metadataCache;
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
        $metadata = new Metadata($class);
        
        if ($this->metadataCache !== null) {
            $key = $this->getCacheKey($class);
            $item = $this->metadataCache->getItem($key);
            if (!$item->isHit()) {
                $this->loader->load($metadata);
                $item->set($metadata);
                $this->metadataCache->save($item);
            }
            
            return $item->get();
        }
        $this->loader->load($metadata);
        
        return $metadata;
    }
    
    protected function getCacheKey(string $class)
    {
        return preg_replace('/[\\\\{}()\\/@]+/', '_', $class);
    }
}