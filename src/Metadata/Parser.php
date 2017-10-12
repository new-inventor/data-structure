<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\PropertiesTransformer;
use NewInventor\Transformers\Transformer\ChainTransformer;
use NewInventor\Transformers\Transformer\ToInt;
use NewInventor\Transformers\TransformerContainerInterface;
use NewInventor\Transformers\TransformerInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class Parser implements ParserInterface
{
    /** @var MetadataInterface|Metadata */
    protected $metadata;
    /** @var ConfigurationInterface */
    protected $configuration;
    /** @var string */
    protected $innerTransformerNamespace;
    
    /**
     * Parser constructor.
     *
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->innerTransformerNamespace = substr(ToInt::class, 0, -5);
    }
    
    /**
     * @param                            $file
     * @param MetadataInterface|Metadata $metadata
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function parse($file, MetadataInterface $metadata): void
    {
        $this->metadata = $metadata;
        $config = $this->readConfigFileToArray($file);
        $processor = new Processor();
        $this->metadata->configArray = $processor->processConfiguration($this->configuration, [$config]);
        if (isset($this->metadata->configArray['properties'])) {
            foreach ($this->metadata->configArray['properties'] as $propertyName => $propertyMetadata) {
                $this->prepareProperty($propertyName, $propertyMetadata);
            }
        }
    }
    
    /**
     * @param string $file
     *
     * @return array
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    protected function readConfigFileToArray(string $file): array
    {
        return Yaml::parse(file_get_contents($file));
    }
    
    /**
     * @param string $file
     *
     * @return string
     */
    protected function getClassNameFromFile(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }
    
    protected function prepareProperty($propertyName, $metadata): void
    {
        $this->metadata->properties[$propertyName] = $metadata['default'];
        if (isset($metadata['nested'])) {
            $this->metadata->nested[$propertyName] = $metadata['nested'];
        }
        
        $transformerGroups = $this->prepareTransformersList($metadata['transformers'], true);
        foreach ($transformerGroups as $group => $transformers) {
            $transformer = null;
            if (count($transformers) === 1) {
                $transformer = $transformers[0];
            } else {
                $transformer = new ChainTransformer(...$transformers);
            }
            $this->setPropertyTransformer($group, $propertyName, $transformer);
        }
    }
    
    protected function setPropertyTransformer(
        string $group,
        string $propertyName,
        TransformerInterface $transformer
    ): void {
        if (!isset($this->metadata->transformers[$group])) {
            $this->metadata->transformers[$group] = new PropertiesTransformer();
        }
        $this->metadata->transformers[$group]->setTransformer($propertyName, $transformer);
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