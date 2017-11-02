<?php

use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\Configuration\Parser\Yaml;
use NewInventor\DataStructure\Loader as DataStructureLoader;
use NewInventor\DataStructure\Metadata\Factory;
use NewInventor\DataStructure\Metadata\Loader;

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
        $parser = new Yaml(new Configuration());
        $loader = new Loader(__DIR__ . '/data', $parser, 'TestsDataStructure');
        $factory = new Factory($loader);
        $loader = new DataStructureLoader\ObjectLoader($factory, 'default');
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
        $parser = new Yaml(new Configuration());
        $loader = new Loader(__DIR__ . '/data', $parser, 'TestsDataStructure');
        $factory = new Factory($loader);
        $loader = new DataStructureLoader\ObjectLoader($factory, 'default');
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
                'prop2' => ['TYPE_EXCEPTION' => ['TO_INT' => 'Type of value invalid.']],
            ],
            $errors
        );
    }
}