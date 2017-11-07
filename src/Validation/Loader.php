<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure\Validation;


use NewInventor\DataStructure\AbstractLoader;
use NewInventor\DataStructure\Configuration\Parser\ParserInterface;
use NewInventor\DataStructure\Exception\MetadataFileNotFoundException;
use NewInventor\Transformers\Transformer\StringToCamelCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;

class Loader extends AbstractLoader implements LoaderInterface
{
    /** @var ClassMetadata */
    protected $metadata;
    /** @var string */
    protected $symfonyValidatorsNamespace;
    
    /**
     * Loader constructor.
     *
     * @param string          $path
     * @param ParserInterface $parser
     * @param string          $baseNamespace
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($path, ParserInterface $parser, $baseNamespace = '')
    {
        parent::__construct($path, $parser, $baseNamespace);
        $this->symfonyValidatorsNamespace = substr(All::class, 0, -3);
    }
    
    /**
     * Loads validation metadata into a {@link ClassMetadata} instance.
     *
     * @param ClassMetadata $metadata The metadata to load
     *
     * @return bool Whether the loader succeeded
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        try {
            $this->load($metadata);
        } catch (MetadataFileNotFoundException $e) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function getMetadataClassName($metadata): string
    {
        return $metadata->getClassName();
    }
    
    /**
     * @param       $metadata
     * @param array $data
     *
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function loadData($metadata, array $data): void
    {
        if (
            isset($data['validation']) &&
            !empty($data['validation']) &&
            is_array($data['validation'])
        ) {
            $this->parseValidation($metadata, $data['validation']);
        }
        if (
            isset($data['properties']) &&
            !empty($data['properties']) &&
            is_array($data['properties'])
        ) {
            $this->parseProperties($metadata, $data['properties']);
        }
    }
    
    /**
     * @param ClassMetadata $metadata
     * @param array         $validationConfig
     *
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    protected function parseValidation($metadata, array $validationConfig): void
    {
        $constraints = $validationConfig['constraints'];
        if (!empty($constraints) && is_array($constraints)) {
            foreach ($constraints as $constraint) {
                $metadata->addConstraint($this->prepareValidator($constraint));
            }
        }
        $getters = $validationConfig['getters'];
        if (!empty($getters) && is_array($getters)) {
            foreach ($getters as $propertyName => $validators) {
                $this->prepareGetterValidators($metadata, $propertyName, $validators);
            }
        }
        $properties = $validationConfig['properties'];
        if (!empty($properties) && is_array($properties)) {
            foreach ($properties as $propertyName => $validators) {
                $this->preparePropertyValidators($metadata, $propertyName, $validators);
            }
        }
    }
    
    /**
     * @param ClassMetadata $metadata
     * @param string        $propertyName
     * @param array         $validators
     */
    protected function prepareGetterValidators($metadata, string $propertyName, array $validators): void
    {
        $propertyName = StringToCamelCase::make()->transform($propertyName);
        foreach ($validators as $validator) {
            $metadata->addGetterConstraint(
                $propertyName,
                $this->prepareValidator($validator)
            );
        }
    }
    
    /**
     * @param ClassMetadata $metadata
     * @param string        $propertyName
     * @param array         $validators
     */
    protected function preparePropertyValidators($metadata, string $propertyName, array $validators): void
    {
        foreach ($validators as $validator) {
            $metadata->addPropertyConstraint(
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
    
    protected function parseProperties($metadata, array $properties)
    {
        foreach ($properties as $propertyName => $propertyMetadata) {
            if (isset($propertyMetadata['validation'])) {
                $this->prepareGetterValidators($metadata, $propertyName, $propertyMetadata['validation']);
            }
        }
    }
}