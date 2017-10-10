<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\Transformers\TransformerInterface;

interface StructureTransformerInterface
{
    public function setTransformer(string $propertyName, TransformerInterface $transformer);
    
    public function setTransformers(array $transformers);
    
    public function getTransformer(string $propertyName): ?TransformerInterface;
    
    /**
     * @param array $properties
     *
     * @return array
     * @throws PropertyTransformationException
     * @throws PropertyInvalidTypeException
     */
    public function transform(array $properties = []): array;
    
    /**
     * @return array
     */
    public function getErrors(): array;
    
    /**
     * @return bool
     */
    public function isFailOnFirstError(): bool;
    
    /**
     * @param bool $failOnFirstError
     *
     * @return $this
     */
    public function setFailOnFirstError(bool $failOnFirstError);
}