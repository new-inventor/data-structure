<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace NewInventor\DataStructure;


interface DataStructureInterface extends Loadable, Arrayable
{
    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $name, $value);
    
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name);
}