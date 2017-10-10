<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use Psr\Cache\CacheItemPoolInterface;

class Loader
{
    /** @var string */
    protected $path;
    /** @var string */
    protected $baseNamespace;
    /** @var CacheItemPoolInterface */
    protected $cacheDriver;
    
    public function __construct(string $path, string $baseNamespace = '')
    {
        if (file_exists($path)) {
            $this->path = $path;
        } else {
            throw new \InvalidArgumentException("Path '$path' does not exists");
        }
        $this->baseNamespace = $baseNamespace;
    }
    
    /**
     * @param CacheItemPoolInterface $cacheDriver
     *
     * @return $this
     */
    public function setCacheDriver(CacheItemPoolInterface $cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * @return string
     */
    public function getBaseNamespace(): string
    {
        return $this->baseNamespace;
    }
    
    public function loadMetadataFor(string $class): Metadata
    {
        if ($this->isReadableDir($this->path)) {
            $path = $this->getFilePath($class);
            if ($this->isReadableFile($path)) {
                return $this->getMatadataObj($path);
            }
            throw new \InvalidArgumentException("Path for class '$class' does not exists.");
        }
        throw new \RuntimeException('Path is file, so use "loadMetadata" method.');
    }
    
    public function loadMetadata(): Metadata
    {
        if ($this->isReadableFile($this->path)) {
            return $this->getMatadataObj($this->path);
        }
        throw new \RuntimeException('Path is directory, so use "loadMetadataFor" method.');
    }
    
    protected function getMatadataObj(string $path)
    {
        if ($this->cacheDriver !== null) {
            $key = $this->getCacheKey($path);
            $item = $this->cacheDriver->getItem($key);
            if (!$item->isHit()) {
                $config = (new Metadata())->loadConfig($path);
                $item->set($config);
                $this->cacheDriver->save($item);
            }
            
            return $item->get();
        }
        
        return (new Metadata())->loadConfig($path);
    }
    
    protected function getCacheKey(string $class)
    {
        return preg_replace('/[\\\\{}()\\/@]+/', '_', $class);
    }
    
    public function getFilePath(string $class): string
    {
        return $this->path .
               str_replace([$this->baseNamespace, '\\'], ['', DIRECTORY_SEPARATOR], $class) .
               '.yml';
    }
    
    protected function isReadableDir(string $path): bool
    {
        return is_dir($path) && is_readable($path);
    }
    
    protected function isReadableFile(string $path): bool
    {
        return is_file($path) && is_readable($path);
    }
}