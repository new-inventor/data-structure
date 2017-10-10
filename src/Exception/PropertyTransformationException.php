<?php
/**
 * Project: property-bag
 * User: george
 * Date: 04.10.17
 */

namespace NewInventor\DataStructure\Exception;


class PropertyTransformationException extends PropertyException
{
    protected $symbolCode = 'NORMALIZATION_EXCEPTION';
    
    /**
     * PropertyException constructor.
     *
     * @param string     $propertyName
     * @param \Throwable $previous
     */
    public function __construct(string $propertyName, \Throwable $previous = null)
    {
        parent::__construct($propertyName, "Normalization of property '$propertyName' failed", $previous);
    }
}