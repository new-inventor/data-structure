<?php
/**
 * Project: TP messaging service
 * User: george
 * Date: 06.09.17
 */

namespace TestsDataStructure;


class TestBag
{
    private $prop0 = 1;
    protected $properties = [
        'prop1' => null,
        'prop2' => 1,
        'prop3' => null,
        'prop4' => null,
        'prop5' => null,
        'prop6' => null,
        'prop7' => null,
        'prop8' => null,
        'prop9' => null,
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
    
    public function getProp5()
    {
        return $this->properties['prop5'];
    }
    
    public function getProp6()
    {
        return $this->properties['prop6'];
    }
    
    public function getProp7()
    {
        return $this->properties['prop7'];
    }
}