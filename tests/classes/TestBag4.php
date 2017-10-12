<?php
/**
 * Project: TP messaging service
 * User: george
 * Date: 06.09.17
 */

namespace TestsDataStructure;


use NewInventor\DataStructure\DataStructureInterface;

class TestBag4 implements DataStructureInterface
{
    protected $properties = [
        'prop1' => null,
        'prop2' => 1,
        'prop3' => null,
    ];
    
    public function getProp1()
    {
        return $this->properties['prop1'];
    }
    
    public function getProp2()
    {
        return $this->properties['prop2'];
    }
    
    public function getProp3()
    {
        return $this->properties['prop3'];
    }
    
    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $name, $value)
    {
        $this->properties[$name] = $value;
        
        return $this;
    }
    
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->properties[$name];
    }
}