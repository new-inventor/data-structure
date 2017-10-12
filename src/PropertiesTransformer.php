<?php
/**
 * Project: property-bag
 * User: george
 * Date: 08.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\Transformers\Exception\TransformationException;
use NewInventor\Transformers\TransformerInterface;
use NewInventor\TypeChecker\Exception\TypeException;

class PropertiesTransformer implements StructureTransformerInterface
{
    /** @var TransformerInterface[] */
    public $transformers = [];
    /** @var array */
    protected $errors = [];
    
    /**
     * DataStructureTransformer constructor.
     *
     * @param TransformerInterface[] $transformers
     */
    public function __construct(array $transformers = [])
    {
        $this->transformers = $transformers;
    }
    
    public function setTransformer(string $propertyName, TransformerInterface $transformer)
    {
        $this->transformers[$propertyName] = $transformer;
        
        return $this;
    }
    
    public function setTransformers(array $transformers)
    {
        foreach ($transformers as $propertyName => $transformer) {
            $this->setTransformer($propertyName, $transformer);
        }
        
        return $this;
    }
    
    public function getTransformer(string $propertyName): ?TransformerInterface
    {
        return $this->transformers[$propertyName] ?? null;
    }
    
    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * @param array $properties
     * @param bool  $mute
     *
     * @return array
     * @throws PropertyTransformationException
     * @throws PropertyInvalidTypeException
     */
    public function transform(array $properties, bool $mute = false): array
    {
        $this->errors = [];
        $res = [];
        foreach ($properties as $name => $value) {
            try {
                if (
                    array_key_exists($name, $this->transformers) &&
                    in_array(TransformerInterface::class, class_implements($this->transformers[$name]), true)
                ) {
                    $res[$name] = $this->transformers[$name]->transform($value);
                } else {
                    $res[$name] = $value;
                }
            } catch (TypeException $e) {
                if (!$mute) {
                    throw new PropertyInvalidTypeException($name, $e);
                }
                $this->errors[$name]['TYPE_EXCEPTION'] = $e->getMessage();
                $res[$name] = $value;
            } catch (TransformationException $e) {
                if (!$mute) {
                    throw new PropertyTransformationException($name, $e);
                }
                $this->errors[$name]['TRANSFORMATION_EXCEPTION'] = $e->getPrevious()->getMessage();
                $res[$name] = $value;
            }
        }
        
        return $res;
    }
}