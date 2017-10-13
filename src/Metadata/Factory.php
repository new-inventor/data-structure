<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\DataStructureInterface;
use NewInventor\TypeChecker\Exception\TypeException;
use NewInventor\TypeChecker\TypeChecker;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;

class Factory
{
    /** @var string */
    protected $basePath;
    /** @var string */
    protected $baseNamespace;
    /** @var CacheItemPoolInterface */
    protected $metadataCache;
    /** @var CacheInterface */
    protected $validationCache;
    
    /**
     * Factory constructor.
     *
     * @param string                 $basePath
     * @param string                 $baseNamespace
     * @param CacheItemPoolInterface $metadataCache
     * @param CacheInterface         $validationCache
     */
    public function __construct(
        string $basePath,
        string $baseNamespace = '',
        CacheItemPoolInterface $metadataCache = null,
        CacheInterface $validationCache = null
    ) {
        $this->basePath = $basePath;
        $this->metadataCache = $metadataCache;
        $this->validationCache = $validationCache;
        $this->baseNamespace = $baseNamespace;
    }
    
    /**
     * @return CacheItemPoolInterface
     */
    public function getMetadataCache(): CacheItemPoolInterface
    {
        return $this->metadataCache;
    }
    
    /**
     * @return CacheInterface
     */
    public function getValidationCache(): CacheInterface
    {
        return $this->validationCache;
    }
    
    /**
     * @param $obj
     *
     * @return MetadataInterface
     * @throws TypeException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function getMetadata($obj): MetadataInterface
    {
        TypeChecker::check($obj)->tstring()->types(DataStructureInterface::class)->fail();
        $class = $obj;
        if (is_object($obj)) {
            $class = get_class($obj);
        }
        if ($this->metadataCache !== null) {
            $key = $this->getCacheKey($class);
            $item = $this->metadataCache->getItem($key);
            if (!$item->isHit()) {
                $config = $this->constructMetadata($class);
                $item->set($config);
                $this->metadataCache->save($item);
            }
            
            return $item->get();
        }
        
        return $this->constructMetadata($class);
    }
    
    /**
     * @param string $class
     *
     * @return MetadataInterface
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \InvalidArgumentException
     */
    protected function constructMetadata(string $class): MetadataInterface
    {
        $config = new Configuration();
        $metadata = new Metadata($class, $this->validationCache);
        $parser = new Parser($config);
        $loader = new Loader($this->basePath, $parser, $this->baseNamespace);
        $loader->loadMetadata($metadata);
        
        return $metadata;
    }
    
    protected function getCacheKey(string $class)
    {
        return preg_replace('/[\\\\{}()\\/@]+/', '_', $class);
    }
}