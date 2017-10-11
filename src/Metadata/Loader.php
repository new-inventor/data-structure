<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Loader
{
    /** @var string */
    protected $path;
    /** @var string */
    protected $baseNamespace;
    /** @var CacheItemPoolInterface */
    protected $cacheDriver;
    /** @var string */
    private $metadataClass;
    
    public function __construct(string $path, string $baseNamespace = '', string $metadataClass = Metadata::class)
    {
        if (file_exists($path)) {
            $this->path = $path;
        } else {
            throw new \InvalidArgumentException("Path '$path' does not exists");
        }
        $this->baseNamespace = trim($baseNamespace, '\t\n\r\0\x0B\\');
        if (
            !class_exists($metadataClass) ||
            !in_array(MetadataInterface::class, class_implements($metadataClass), true)
        ) {
            throw new \InvalidArgumentException('Metadata class must implement ' . MetadataInterface::class);
        }
        $this->metadataClass = $metadataClass;
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
    
    /**
     * @param string $class
     * @param ConfigurationInterface $configuration
     *
     * @return mixed
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function loadMetadataFor(string $class, ConfigurationInterface $configuration)
    {
        if ($this->isReadableDir($this->path)) {
            $path = $this->getFilePath($class);
            if ($this->isReadableFile($path)) {
                return $this->getMetadataObj($path, $configuration);
            }
            throw new \InvalidArgumentException("Path for class '$class' does not exists.");
        }
        throw new \RuntimeException('Path is file, so use "loadMetadata" method.');
    }
    
    /**
     * @param ConfigurationInterface $configuration
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function loadMetadata(ConfigurationInterface $configuration)
    {
        if ($this->isReadableFile($this->path)) {
            return $this->getMetadataObj($this->path, $configuration);
        }
        throw new \RuntimeException('Path is directory, so use "loadMetadataFor" method.');
    }
    
    protected function getMetadataObj(string $path, ConfigurationInterface $configuration)
    {
        if ($this->cacheDriver !== null) {
            $key = $this->getCacheKey($path);
            $item = $this->cacheDriver->getItem($key);
            if (!$item->isHit()) {
                $config = $this->constructMetadata($path, $configuration);
                $item->set($config);
                $this->cacheDriver->save($item);
            }
            
            return $item->get();
        }
    
        return $this->constructMetadata($path, $configuration);
    }
    
    protected function constructMetadata($path, ConfigurationInterface $configuration)
    {
        $class = $this->metadataClass;
    
        /** @noinspection PhpUndefinedMethodInspection */
        return (new $class())->loadConfig($path, $configuration);
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