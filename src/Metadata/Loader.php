<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure\Metadata;


class Loader
{
    /** @var string */
    protected $path;
    /** @var string */
    protected $baseNamespace;
    /** @var ParserInterface */
    protected $parser;
    
    public function __construct(string $path, ParserInterface $parser, string $baseNamespace = '')
    {
        if (file_exists($path)) {
            $this->path = $path;
        } else {
            throw new \InvalidArgumentException("Path '$path' does not exists");
        }
        $this->parser = $parser;
        $this->baseNamespace = trim($baseNamespace, "\t\n\r\0\x0B\\/");
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
     * @param MetadataInterface $metadata
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function loadMetadata(MetadataInterface $metadata): void
    {
        if ($this->isReadableDir($this->path)) {
            $path = $this->getFilePath($metadata);
            if ($this->isReadableFile($path)) {
                $this->parser->parse($path, $metadata);
            } else {
                throw new \InvalidArgumentException(
                    "Path '$path' for class '{$metadata->getFullClassName()}' does not exists."
                );
            }
        } else if ($this->isReadableFile($this->path)) {
            $this->parser->parse($this->path, $metadata);
        }
    }
    
    public function getFilePath(MetadataInterface $metadata): string
    {
        return $this->path . DIRECTORY_SEPARATOR .
               str_replace([$this->baseNamespace, '\\'], ['', DIRECTORY_SEPARATOR], $metadata->getFullClassName()) .
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