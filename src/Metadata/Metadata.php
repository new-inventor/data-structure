<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\PropertiesTransformer;
use NewInventor\DataStructure\StructureTransformerInterface;
use NewInventor\DataStructure\Validation\Loader;
use NewInventor\Transformers\Transformer\ChainTransformer;
use NewInventor\Transformers\TransformerContainerInterface;
use NewInventor\Transformers\TransformerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;

class Metadata implements MetadataIntraface
{
    /** @var string */
    protected $namespace = '';
    /** @var string */
    protected $className;
    /** @var ClassMetadata */
    protected $classValidationMetadata;
    /** @var ValidatorInterface */
    protected $classValidator;
    /** @var string[] */
    protected $properties = [];
    /** @var StructureTransformerInterface[] */
    protected $transformers = [];
    /** @var array[] */
    protected $nested = [];
    /** @var array */
    protected $configArray = [];
    
    /**
     * @param string $file
     *
     * @return mixed
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public static function getConfig(string $file)
    {
        return Yaml::parse(file_get_contents($file));
    }
    
    /**
     * @param string $file
     *
     * @return mixed
     */
    public static function getClassNameFromFile(string $file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }
    
    /**
     * @param string $file
     *
     * @return $this
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function loadConfig(string $file)
    {
        $config = self::getConfig($file);
        $this->className = self::getClassNameFromFile($file);
        $processor = new Processor();
        $this->configArray = $processor->processConfiguration(new Configuration(), [$config]);
        if (isset($this->configArray['namespace'])) {
            $this->namespace = $this->configArray['namespace'];
        }
        if (isset($this->configArray['properties'])) {
            foreach ($this->configArray['properties'] as $propertyName => $metadata) {
                $this->prepareProperty($propertyName, $metadata);
            }
        }
        
        return $this;
    }
    
    protected function prepareMethods($config)
    {
        if ($config['only'] !== []) {
            return $config['only'];
        }
        if ($config['except'] !== []) {
            return array_values(array_diff(array_keys($this->properties), $config['except']));
        }
    
        return array_keys($this->properties);
    }
    
    protected function prepareProperty($propertyName, $metadata): void
    {
        $this->properties[$propertyName] = $metadata['default'];
        if (isset($metadata['nested'])) {
            $this->nested[$propertyName] = $metadata['nested'];
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
        $group,
        string $propertyName,
        TransformerInterface $transformer
    ): void {
        if (!isset($this->transformers[$group])) {
            $this->transformers[$group] = new PropertiesTransformer();
        }
        $this->transformers[$group]->setTransformer($propertyName, $transformer);
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
            return new $transformerClass(...$this->prepareTransformersList($parameters));
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
    
        return 'NewInventor\Transformers\Transformer\\' . $name;
    }
    
    protected function initValidation(): void
    {
        $this->classValidationMetadata = new ClassMetadata($this->getFullClassName());
        if (!isset($this->configArray['validation'])) {
            return;
        }
        if (isset($this->configArray['validation']['constraints']) && is_array($this->configArray['validation']['constraints'])) {
            foreach ($this->configArray['validation']['constraints'] as $constraint) {
                $this->classValidationMetadata->addConstraint($this->prepareValidator($constraint));
            }
        }
        if (isset($this->configArray['validation']['getters']) && is_array($this->configArray['validation']['getters'])) {
            foreach ($this->configArray['validation']['getters'] as $propertyName => $validators) {
                if (!array_key_exists($propertyName, $this->properties)) {
                    $this->properties[$propertyName] = null;
                }
                $this->prepareGetterValidators($propertyName, $validators);
            }
        }
        if (isset($this->configArray['validation']['properties']) && is_array($this->configArray['validation']['properties'])) {
            foreach ($this->configArray['validation']['properties'] as $propertyName => $validators) {
                if (!array_key_exists($propertyName, $this->properties)) {
                    $this->properties[$propertyName] = null;
                }
                $this->preparePropertyValidators($propertyName, $validators);
            }
        }
    }
    
    protected function createValidator(CacheInterface $cacheDriver = null): RecursiveValidator
    {
        $metadataFactory = new LazyLoadingMetadataFactory(new Loader($this->classValidationMetadata), $cacheDriver);
        
        $validatorFactory = new ConstraintValidatorFactory();
        $translator = new IdentityTranslator();
        $translator->setLocale('en');
        
        $contextFactory = new ExecutionContextFactory($translator, null);
        
        return new RecursiveValidator(
            $contextFactory,
            $metadataFactory,
            $validatorFactory
        );
    }
    
    protected function prepareGetterValidators(string $propertyName, array $validators): void
    {
        foreach ($validators as $validator) {
            $this->classValidationMetadata->addGetterConstraint($propertyName, $this->prepareValidator($validator));
        }
    }
    
    protected function preparePropertyValidators(string $propertyName, array $validators): void
    {
        foreach ($validators as $validator) {
            $this->classValidationMetadata->addPropertyConstraint($propertyName, $this->prepareValidator($validator));
        }
    }
    
    protected function prepareValidator($validator)
    {
        $validatorClass = $validatorName = array_keys($validator)[0];
        if (!class_exists($validatorName)) {
            $validatorClass = 'Symfony\Component\Validator\Constraints\\' . $validatorName;
        }
        if (!in_array(Constraint::class, class_parents($validatorClass), true)) {
            throw new \InvalidArgumentException('Validator must extend ' . Constraint::class);
        }
        
        return new $validatorClass($validator[$validatorName]);
    }
    
    /**
     * @return string
     */
    public function getFullClassName(): string
    {
        return $this->namespace . '\\' . $this->className;
    }
    
    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
    
    /**
     * @param CacheInterface $cacheDriver
     *
     * @return ValidatorInterface
     */
    public function getValidator(CacheInterface $cacheDriver = null): ValidatorInterface
    {
        if($this->classValidator === null) {
            $this->initValidation();
            foreach ($this->configArray['properties'] as $propertyName => $metadata) {
                $this->prepareGetterValidators($propertyName, $metadata['validation']);
            }
            $this->classValidator = $this->createValidator($cacheDriver);
            unset($this->classValidationMetadata);
        }
        return $this->classValidator;
    }
    
    /**
     * @return string[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
    
    /**
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME
    ): StructureTransformerInterface {
        return $this->transformers[$group];
    }
    
    /**
     * @return array
     */
    public function getNested(): array
    {
        return $this->nested;
    }
}