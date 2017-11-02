<?php
/**
 * Project: data-structure
 * User: george
 * Date: 02.11.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Configuration\Parser\ParserInterface;
use NewInventor\DataStructure\Helper\ObjectHelper;

abstract class AbstractLoader implements MetadataLoaderInterface
{
    /** @var string */
    protected $path;
    /** @var string */
    protected $baseNamespace;
    /** @var ParserInterface */
    protected $parser;
    
    /**
     * AbstractLoader constructor.
     *
     * @param string          $path
     * @param ParserInterface $parser
     * @param string          $baseNamespace
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $path, ParserInterface $parser, string $baseNamespace = '')
    {
        if (file_exists($path)) {
            $this->path = $path;
        } else {
            throw new \InvalidArgumentException("Path '$path' does not exists");
        }
        $this->parser = $parser;
        $this->baseNamespace = $baseNamespace;
    }
    
    /**
     * @param $metadata
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function load($metadata): void
    {
        if ($this->isReadableDir($this->path)) {
            $metadataClassName = $this->getMetadataClassName($metadata);
            $path = $this->getFilePath($metadataClassName);
            if ($this->isReadableFile($path)) {
                $array = $this->parser->parse($path);
                $this->loadData($metadata, $array);
            } else {
                throw new \InvalidArgumentException(
                    "Path '$path' for class '{$metadataClassName}' does not exists."
                );
            }
        } else if ($this->isReadableFile($this->path)) {
            $array = $this->parser->parse($this->path);
            $this->loadData($metadata, $array);
        }
    }
    
    abstract protected function getMetadataClassName($metadata);
    
    abstract protected function loadData($metadata, array $data);
    
    protected function getFilePath(string $className): string
    {
        $helper = ObjectHelper::make();
        
        return $helper->getFilePathForClass(
            $this->path,
            $helper->replaceNamespaceDelimiter(
                $helper->truncateBaseNamespace(
                    $className,
                    $this->baseNamespace
                ),
                DIRECTORY_SEPARATOR
            ),
            'yml'
        );
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