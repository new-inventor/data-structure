<?php
/**
 * Project: property-bag
 * User: george
 * Date: 08.10.17
 */

namespace TestsDataStructure;



class TestBag1
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
}