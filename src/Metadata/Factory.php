<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure\Metadata;


use NewInventor\DataStructure\DataStructureInterface;
use NewInventor\TypeChecker\Exception\TypeException;
use NewInventor\TypeChecker\TypeChecker;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class Factory
{
    /** @var string */
    protected $basePath;
    /** @var string */
    protected $baseNamespace;
    /** @var CacheItemPoolInterface */
    protected $metadataCache;
    /** @var CacheInterface */
    protected $validationCache;
    
    /**
     * Factory constructor.
     *
     * @param string                 $basePath
     * @param string                 $baseNamespace
     * @param CacheItemPoolInterface $metadataCache
     * @param CacheInterface         $validationCache
     */
    public function __construct(
        string $basePath,
        string $baseNamespace = '',
        CacheItemPoolInterface $metadataCache = null,
        CacheInterface $validationCache = null
    ) {
        $this->basePath = $basePath;
        $this->metadataCache = $metadataCache;
        $this->validationCache = $validationCache;
        $this->baseNamespace = $baseNamespace;
    }
    
    /**
     * @return CacheItemPoolInterface
     */
    public function getMetadataCache(): CacheItemPoolInterface
    {
        return $this->metadataCache;
    }
    
    /**
     * @return CacheInterface
     */
    public function getValidationCache(): CacheInterface
    {
        return $this->validationCache;
    }
    
    /**
     * @param $obj
     *
     * @return MetadataInterface
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws TypeException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function getMetadata($obj): MetadataInterface
    {
        TypeChecker::check($obj)->tstring()->types(DataStructureInterface::class)->fail();
        $class = is_object($obj) ? get_class($obj) : $obj;
        if ($this->metadataCache !== null) {
            $key = $this->getCacheKey($class);
            $item = $this->metadataCache->getItem($key);
            if (!$item->isHit()) {
                $config = $this->constructMetadata($class);
                $item->set($config);
                $this->metadataCache->save($item);
            }
            
            return $item->get();
        }
        
        return $this->constructMetadata($class);
    }
    
    /**
     * @param MetadataInterface $metadata
     *
     * @return RecursiveValidator
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     */
    public function getValidatorFromMetadata(MetadataInterface $metadata): RecursiveValidator
    {
        $loader = new \NewInventor\DataStructure\Validation\Loader($metadata->getValidationMetadata());
        $metadataFactory = new LazyLoadingMetadataFactory($loader, $this->validationCache);
        
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
     * @param string $class
     *
     * @return MetadataInterface
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \InvalidArgumentException
     */
    protected function constructMetadata(string $class): MetadataInterface
    {
        $config = new Configuration();
        $metadata = new Metadata($class);
        $parser = new Parser($config);
        $loader = new Loader($this->basePath, $parser, $this->baseNamespace);
        $loader->loadMetadata($metadata);
        
        return $metadata;
    }
    
    protected function getCacheKey(string $class)
    {
        return preg_replace('/[\\\\{}()\\/@]+/', '_', $class);
    }
}