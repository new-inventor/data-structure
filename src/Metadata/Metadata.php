<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\StructureTransformerInterface;
use NewInventor\DataStructure\Validation\Loader;
use Symfony\Component\Translation\IdentityTranslator;
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
    }
    
    protected function createValidator(): ?RecursiveValidator
    {
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