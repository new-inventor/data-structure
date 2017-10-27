<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\PropertiesTransformer;
use NewInventor\Transformers\Transformer\ChainTransformer;
use NewInventor\Transformers\Transformer\StringToCamelCase;
use NewInventor\Transformers\Transformer\ToInt;
use NewInventor\Transformers\TransformerContainerInterface;
use NewInventor\Transformers\TransformerInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Yaml\Yaml;

class Parser implements ParserInterface
{
    /** @var MetadataInterface|Metadata */
    protected $metadata;
    /** @var ConfigurationInterface */
    protected $configuration;
    /** @var string */
    protected $innerTransformerNamespace;
    /** @var string */
    protected $symfonyValidatorsNamespace;
    
    /**
     * Parser constructor.
     *
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->innerTransformerNamespace = substr(ToInt::class, 0, -5);
        $this->symfonyValidatorsNamespace = substr(All::class, 0, -3);
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
        if (
            isset($this->metadata->configArray['validation']) &&
            !empty($this->metadata->configArray['validation']) &&
            is_array($this->metadata->configArray['validation'])
        ) {
            $this->parseValidation($this->metadata->configArray['validation']);
        }
        if (
            isset($this->metadata->configArray['properties']) &&
            !empty($this->metadata->configArray['properties']) &&
            is_array($this->metadata->configArray['properties'])
        ) {
            $this->parseProperties($this->metadata->configArray['properties']);
        }
    }
    
    protected function parseProperties(array $properties)
    {
        foreach ($properties as $propertyName => $propertyMetadata) {
            $this->prepareProperty($propertyName, $propertyMetadata);
            if (isset($propertyMetadata['validation'])) {
                $this->prepareGetterValidators($propertyName, $propertyMetadata['validation']);
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
    
    protected function parseValidation(array $validationConfig): void
    {
        $constraints = $validationConfig['constraints'];
        if (!empty($constraints) && is_array($constraints)) {
            foreach ($constraints as $constraint) {
                $this->metadata->validationMetadata->addConstraint($this->prepareValidator($constraint));
            }
        }
        $getters = $validationConfig['getters'];
        if (!empty($getters) && is_array($getters)) {
            foreach ($getters as $propertyName => $validators) {
                if (!array_key_exists($propertyName, $this->metadata->properties)) {
                    $this->metadata->properties[$propertyName] = null;
                }
                $this->prepareGetterValidators($propertyName, $validators);
            }
        }
        $properties = $validationConfig['properties'];
        if (!empty($properties) && is_array($properties)) {
            foreach ($properties as $propertyName => $validators) {
                if (!array_key_exists($propertyName, $this->metadata->properties)) {
                    $this->metadata->properties[$propertyName] = null;
                }
                $this->preparePropertyValidators($propertyName, $validators);
            }
        }
    }
    
    protected function prepareGetterValidators(string $propertyName, array $validators): void
    {
        $propertyName = StringToCamelCase::make()->transform($propertyName);
        foreach ($validators as $validator) {
            $this->metadata->validationMetadata->addGetterConstraint(
                $propertyName,
                $this->prepareValidator($validator)
            );
        }
    }
    
    protected function preparePropertyValidators(string $propertyName, array $validators): void
    {
        foreach ($validators as $validator) {
            $this->metadata->validationMetadata->addPropertyConstraint(
                $propertyName,
                $this->prepareValidator($validator)
            );
        }
    }
    
    protected function prepareValidator($validator)
    {
        $validatorClass = $validatorName = array_keys($validator)[0];
        if (!class_exists($validatorName)) {
            $validatorClass = $this->symfonyValidatorsNamespace . $validatorName;
        }
        if (!in_array(Constraint::class, class_parents($validatorClass), true)) {
            throw new \InvalidArgumentException('Validator must extend ' . Constraint::class);
        }
        
        return new $validatorClass($validator[$validatorName]);
    }
}