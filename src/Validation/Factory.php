<?php
/**
 * Project: data-structure
 * User: george
 * Date: 02.11.17
 */

namespace NewInventor\DataStructure\Validation;


use NewInventor\DataStructure\MetadataLoaderInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class Factory
{
    /** @var CacheInterface */
    protected $cache;
    protected $loader;
    
    /**
     * Factory constructor.
     *
     * @param MetadataLoaderInterface|null $loader
     * @param CacheInterface               $validationCache
     */
    public function __construct(
        MetadataLoaderInterface $loader,
        CacheInterface $validationCache = null
    ) {
        $this->cache = $validationCache;
        $this->loader = $loader;
    }
    
    /**
     * @param $obj
     *
     * @return RecursiveValidator
     * @throws \InvalidArgumentException
     * @throws \NewInventor\TypeChecker\Exception\TypeException
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     */
    public function getValidator(): RecursiveValidator
    {
        $metadataFactory = new LazyLoadingMetadataFactory($this->loader, $this->cache);
        
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
}