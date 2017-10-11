<?php
/**
 * Project: TP messaging service
 * User: george
 * Date: 06.09.17
 */

namespace TestsDataStructure;


use NewInventor\DataStructure\Loadable;

class TestBag5 implements Loadable
{
    protected $properties = [
        'prop1' => null,
        'prop2' => 1,
        'prop3' => null,
        'prop4' => null,
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
    
    public function getProp4()
    {
        return $this->properties['prop4'];
    }
    
    /**
     * Load object properties from array
     *
     * @param array $properties
     * @param int   $strategy
     *
     * @return $this
     */
    public function load(array $properties = [], int $strategy = self::STRATEGY_STRICT)
    {
        foreach ($properties as $property => $value) {
            $this->properties[$property] = $value;
        }
        
        return $this;
    }
}