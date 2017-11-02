<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\AbstractLoader;
use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\Configuration\Parser\ParserInterface;
use NewInventor\DataStructure\PropertiesTransformer;
use NewInventor\DataStructure\StructureTransformerInterface;
use NewInventor\Transformers\Transformer\ChainTransformer;
use NewInventor\Transformers\Transformer\ToInt;
use NewInventor\Transformers\TransformerContainerInterface;
use NewInventor\Transformers\TransformerInterface;

class Loader extends AbstractLoader
{
    protected $innerTransformerNamespace;
    
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
        parent::__construct($path, $parser, $baseNamespace);
        $this->innerTransformerNamespace = substr(ToInt::class, 0, -5);
    }
    
    /**
     * @param MetadataInterface|Metadata $metadata
     *
     * @return string
     */
    protected function getMetadataClassName($metadata): string
    {
        return $metadata->getFullClassName();
    }
    
    /**
     * @param MetadataInterface|Metadata $metadata
     * @param array                      $data
     */
    public function loadData($metadata, array $data): void
    {
        if (
            isset($data['properties']) &&
            !empty($data['properties']) &&
            is_array($data['properties'])
        ) {
            $this->parseProperties($metadata, $data['properties']);
        }
    }
    
    protected function parseProperties(Metadata $metadata, array $properties)
    {
        foreach ($properties as $propertyName => $propertyData) {
            $this->prepareProperty($metadata, $propertyName, $propertyData);
        }
    }
    
    protected function prepareProperty(Metadata $metadata, $propertyName, $propertyData): void
    {
        $metadata->properties[$propertyName] = $propertyData['default'];
        if (isset($propertyData['nested'])) {
            $metadata->nested[$propertyName] = $propertyData['nested'];
        }
        
        $transformerGroups = $this->prepareTransformersList($propertyData['transformers'], true);
        foreach ($transformerGroups as $group => $transformers) {
            $transformer = null;
            if (count($transformers) === 1) {
                $transformer = $transformers[0];
            } else {
                $transformer = new ChainTransformer(...$transformers);
            }
            $structureTransformer = $this->getPropertiesTransformer($metadata, $group);
            $structureTransformer->setTransformer($propertyName, $transformer);
        }
    }
    
    protected function getPropertiesTransformer($metadata, $group): StructureTransformerInterface
    {
        if (!isset($metadata->transformers[$group])) {
            return $metadata->transformers[$group] = new PropertiesTransformer();
        }
    
        return $metadata->transformers[$group];
    }
    
    protected function prepareTransformersList(array $transformers, $firstLevel = false): array
    {
        $transformersList = [];
        foreach ($transformers as $transformerData) {
            $transformerName = array_keys($transformerData)[0];
            $parameters = $transformerData[$transformerName];
            if ($firstLevel) {
                $groups = $this->extractGroups($parameters);
                $groups = $this->normalizeGroups($groups);
                $transformer = $this->prepareTransformer($transformerName, $parameters);
                foreach ($groups as $group) {
                    $transformersList[$group][] = $transformer;
                }
            } else {
                $transformersList[] = $this->prepareTransformer($transformerName, $parameters);
            }
        }
    
        return $transformersList;
    }
    
    /**
     * @param string $name
     * @param mixed  $parameters
     *
     * @return mixed
     */
    protected function prepareTransformer(string $name, array $parameters)
    {
        $transformerClass = $this->getFullName($name);
        if (
            class_exists($transformerClass) &&
            in_array(TransformerContainerInterface::class, class_implements($transformerClass), true)
        ) {
            $parameters = $this->prepareTransformersList($parameters);
        }
    
        if (empty($parameters)) {
            return new $transformerClass();
        }
    
        return new $transformerClass(...$parameters);
    }
    
    protected function normalizeGroups(array $parameters): array
    {
        return $parameters === [] ? [Configuration::DEFAULT_GROUP_NAME] : $parameters;
    }
    
    protected function extractGroups(array &$parameters): array
    {
        foreach ($parameters as $key => $parameter) {
            if (is_array($parameter) && count($parameter) === 1 && array_keys($parameter)[0] === 'groups') {
                unset($parameters[$key]);
                
                return $parameter['groups'];
            }
        }
        
        return [];
    }
    
    protected function getFullName(string $name): string
    {
        if (class_exists($name) && in_array(TransformerInterface::class, class_implements($name), true)) {
            return $name;
        }
        
        return $this->innerTransformerNamespace . $name;
    }
}