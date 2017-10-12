<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\StructureTransformerInterface;
use NewInventor\DataStructure\Validation\Loader;
use NewInventor\Transformers\Transformer\StringToCamelCase;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Metadata implements MetadataInterface
{
    /** @var string */
    public $namespace;
    /** @var string */
    public $className;
    /** @var string */
    public $fullClassName;
    /** @var ClassMetadata */
    public $classValidationMetadata;
    /** @var ValidatorInterface */
    public $classValidator;
    /** @var string[] */
    public $properties = [];
    /** @var StructureTransformerInterface[] */
    public $transformers = [];
    /** @var array[] */
    public $nested = [];
    /** @var array */
    public $configArray = [];
    /** @var null|CacheInterface */
    protected $validatorCacheDriver;
    /** @var string */
    protected $symfonyValidatorsNamespace;
    
    /**
     * Metadata constructor.
     *
     * @param string              $class
     * @param CacheInterface|null $validatorCacheDriver
     */
    public function __construct(string $class, CacheInterface $validatorCacheDriver = null)
    {
        $this->validatorCacheDriver = $validatorCacheDriver;
        if (class_exists($class)) {
            $this->fullClassName = $class;
            $lastDelimiterPos = strrpos($class, '\\');
            if ($lastDelimiterPos === false) {
                $lastDelimiterPos = null;
            }
            $this->className = substr($class, $lastDelimiterPos ? $lastDelimiterPos + 1 : 0);
            $this->namespace = trim(substr($class, 0, $lastDelimiterPos), "\t\n\r\0\x0B\\/");
        }
        $this->symfonyValidatorsNamespace = substr(All::class, 0, -3);
    }
    
    protected function initValidation(): void
    {
        $this->classValidationMetadata = new ClassMetadata($this->getFullClassName());
        if (!isset($this->configArray['validation'])) {
            return;
        }
        $classValidation = $this->configArray['validation'];
        if (isset($classValidation['constraints']) && is_array($classValidation['constraints'])) {
            foreach ($classValidation['constraints'] as $constraint) {
                $this->classValidationMetadata->addConstraint($this->prepareValidator($constraint));
            }
        }
        if (isset($classValidation['getters']) && is_array($classValidation['getters'])) {
            foreach ($classValidation['getters'] as $propertyName => $validators) {
                if (!array_key_exists($propertyName, $this->properties)) {
                    $this->properties[$propertyName] = null;
                }
                $this->prepareGetterValidators($propertyName, $validators);
            }
        }
        if (isset($classValidation['properties']) && is_array($classValidation['properties'])) {
            foreach ($classValidation['properties'] as $propertyName => $validators) {
                if (!array_key_exists($propertyName, $this->properties)) {
                    $this->properties[$propertyName] = null;
                }
                $this->preparePropertyValidators($propertyName, $validators);
            }
        }
    }
    
    protected function prepareGetterValidators(string $propertyName, array $validators): void
    {
        $propertyName = StringToCamelCase::make()->transform($propertyName);
        foreach ($validators as $validator) {
            $this->classValidationMetadata->addGetterConstraint(
                $propertyName,
                $this->prepareValidator($validator)
            );
        }
    }
    
    protected function preparePropertyValidators(string $propertyName, array $validators): void
    {
        foreach ($validators as $validator) {
            $this->classValidationMetadata->addPropertyConstraint(
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
    
    protected function createValidator(): ?RecursiveValidator
    {
        $this->initValidation();
        if (isset($this->configArray['properties'])) {
            foreach ($this->configArray['properties'] as $propertyName => $propertyMetadata) {
                $this->prepareGetterValidators($propertyName, $propertyMetadata['validation']);
            }
        }
        if ($this->classValidationMetadata === null) {
            return null;
        }
        $metadataFactory = new LazyLoadingMetadataFactory(
            new Loader($this->classValidationMetadata),
            $this->validatorCacheDriver
        );
        
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
    
    /**
     * @return string
     */
    public function getFullClassName(): string
    {
        return $this->fullClassName;
    }
    
    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
    
    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    /**
     * @return array
     */
    public function getConfigArray(): array
    {
        return $this->configArray;
    }
    
    /**
     * @return ValidatorInterface
     */
    public function getValidator(): ?ValidatorInterface
    {
        if($this->classValidator === null) {
            $this->classValidator = $this->createValidator();
            unset($this->classValidationMetadata);
        }
        return $this->classValidator;
    }
    
    /**
     * @param string $group
     *
     * @return StructureTransformerInterface
     */
    public function getTransformer(string $group = Configuration::DEFAULT_GROUP_NAME): StructureTransformerInterface
    {
        return $this->transformers[$group];
    }
}