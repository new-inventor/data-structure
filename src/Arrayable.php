<?php
/**
 * Project: property-bag
 * User: george
 * Date: 05.10.17
 */

namespace NewInventor\DataStructure;


interface Arrayable
{
    /**
     * Return array representation of object
     * @return array
     */
    public function toArray(): array;
}