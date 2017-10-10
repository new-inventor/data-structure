<?php
/**
 * Project: property-bag
 * User: george
 * Date: 03.10.17
 */

namespace NewInventor\DataStructure\Exception;


class PropertyException extends \InvalidArgumentException
{
    /** @var string */
    protected $symbolCode = 'PROPERTY_NOT_VALID';
    /** @var string */
    protected $propertyName;
    
    /**
     * PropertyException constructor.
     *
     * @param string     $propertyName
     * @param string     $message
     * @param \Throwable $previous
     */
    public function __construct(string $propertyName, string $message = null, \Throwable $previous = null)
    {
        parent::__construct('', 0, $previous);
        $this->propertyName = $propertyName;
        $this->message = $message ?? "Property $propertyName has not valid value";
    }
    
    /**
     * @return string
     */
    public function getSymbolCode(): string
    {
        return $this->symbolCode;
    }
    
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}