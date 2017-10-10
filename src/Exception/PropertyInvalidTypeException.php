<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Exception;


class PropertyInvalidTypeException extends PropertyException
{
    protected $symbolCode = 'TYPE_EXCEPTION';
    
    /**
     * PropertyException constructor.
     *
     * @param string     $propertyName
     * @param \Throwable $previous
     */
    public function __construct(string $propertyName, \Throwable $previous = null)
    {
        parent::__construct($propertyName, "Property '$propertyName' has invalid value type", $previous);
    }
}