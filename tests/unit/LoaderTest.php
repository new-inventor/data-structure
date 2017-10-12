<?php

use NewInventor\DataStructure\Loader as DataStructureLoader;
use NewInventor\DataStructure\Metadata\Factory;

class LoaderTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }
    
    protected function _after()
    {
    }
    
    // tests
    public function testSomeFeature()
    {
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new DataStructureLoader($factory, 'default', true);
        $bag = new \TestsDataStructure\TestBag4();
        $loader->load(
            $bag,
            [
                'prop1' => '123,456,true,123.3423',
                'prop2' => '3',
                'prop3' => '1',
            ]
        );
        $this->assertSame(
            [
                123,
                456,
                true,
                123.3423,
            ],
            $bag->getProp1()
        );
    }
    
    // tests
    public function test1()
    {
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new DataStructureLoader($factory, 'default', true);
        $bag = new \TestsDataStructure\TestBag4();
        $errors = $loader->load(
            $bag,
            [
                'prop1' => '123,456,true,123.3423',
                'prop2' => 'asd',
                'prop3' => '1',
            ]
        );
        $this->assertSame(
            [
                123,
                456,
                true,
                123.3423,
            ],
            $bag->getProp1()
        );
        $this->assertSame(
            [
                'prop2' => ['TYPE_EXCEPTION' => "The type of the variable in the method NewInventor\Transformers\Transformer\ToInt->validateInputTypes is incorrect.\nRequired type is: numeric \nType received: string"],
            ],
            $errors
        );
    }
}