<?php
/**
 * Project: property-bag
 * User: george
 * Date: 08.10.17
 */

namespace NewInventor\DataStructure;


use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\Transformers\ArrayTransformerContainerInterface;
use NewInventor\Transformers\Exception\TransformationContainerException;
use NewInventor\Transformers\Exception\TransformationException;
use NewInventor\Transformers\Exception\TypeException;
use NewInventor\Transformers\TransformerContainerInterface;
use NewInventor\Transformers\TransformerInterface;

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
    
    public function getTransformers()
    {
        return $this->transformers;
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
     *
     * @return array
     * @throws PropertyTransformationException
     * @throws PropertyInvalidTypeException
     */
    public function transform(array $properties): array
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
                $this->errors[$name] = $this->exceptionToArray($e);
                $res[$name] = $value;
            } catch (TransformationException $e) {
                $this->errors[$name] = $this->exceptionToArray($e);
                $res[$name] = $value;
            } catch (TransformationContainerException $e) {
                $this->errors[$name] = $this->exceptionToArray($e);
                $res[$name] = $value;
            }
        }
        
        return $res;
    }
    
    protected function exceptionToArray(\Throwable $e)
    {
        if (get_class($e) === TypeException::class) {
            /**@var TypeException $e */
            return ['TYPE_EXCEPTION' => [$e->getStringCode() => $e->getMessage()]];
        }
        if (get_class($e) === TransformationException::class) {
            /**@var TransformationException $e */
            return ['TRANSFORMATION_EXCEPTION' => [$e->getStringCode() => $e->getMessage()]];
        }
        if (get_class($e) === TransformationContainerException::class) {
            /**@var TransformationContainerException $e */
            $innerExceptions = $e->getInner();
            $isArrayTransformer = in_array(
                ArrayTransformerContainerInterface::class,
                class_implements($e->getClassName()),
                true
            );
            $inner = [];
            /**@var TransformationContainerException|TransformationException|TypeException $exception */
            foreach ($innerExceptions as $key => $exception) {
                if ($isArrayTransformer) {
                    $inner[$key] = $this->exceptionToArray($exception);
                } else {
                    $inner[$exception->getStringCode()] = $this->exceptionToArray($exception);
                }
            }
            if (
                !$isArrayTransformer &&
                in_array(
                    TransformerContainerInterface::class,
                    class_implements($e->getClassName()),
                    true
                )
            ) {
                return ['TRANSFORMATION_CONTAINER_EXCEPTION' => $inner];
            }
            
            return $inner;
        }
        
        return [];
    }
}