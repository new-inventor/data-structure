<?php
/**
 * Project: TP messaging service
 * User: george
 * Date: 29.08.17
 */

namespace NewInventor\DataStructure\Exception;


class PropertyNotFoundException extends PropertyException
{
    /** @var string */
    protected $symbolCode = 'PROPERTY_NOT_FOUND';
    /**
     * PropertyException constructor.
     *
     * @param string     $propertyName
     * @param \Throwable $previous
     */
    public function __construct(string $propertyName, \Throwable $previous = null)
    {
        parent::__construct($propertyName, "Property '$propertyName' not found", $previous);
    }
}