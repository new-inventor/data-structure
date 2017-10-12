<?php

use NewInventor\DataStructure\Metadata\Configuration;
use NewInventor\DataStructure\Metadata\Factory;
use NewInventor\DataStructure\RecursiveLoader;
use TestsDataStructure\TestBag3;
use TestsDataStructure\TestBag5;

class RecursiveLoaderTest extends \Codeception\Test\Unit
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
        $properties = [
            'prop1' => '6545',
            'prop2' => '123',
            'prop3' => true,
            'prop4' => [
                'prop1' => '123,456,true,123.3423',
                'prop2' => '3',
                'prop3' => '1',
            ],
        ];
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new RecursiveLoader($factory, Configuration::DEFAULT_GROUP_NAME, false);
        $bag = new TestBag3();
        $loader->load($bag, $properties);
        $this->assertSame('6545', $bag->getProp1());
        $this->assertSame(123, $bag->getProp2());
        $this->assertTrue($bag->getProp3());
        $this->assertSame('TestsDataStructure\TestBag4', get_class($bag->getProp4()));
        /** @var TestsDataStructure\TestBag4 $nested */
        $nested = $bag->getProp4();
        $this->assertSame(
            [
                123,
                456,
                true,
                123.3423,
            ],
            $nested->getProp1()
        );
        $this->assertSame(3, $nested->getProp2());
        $this->assertTrue($nested->getProp3());
    }
    
    public function test1()
    {
        $properties = [
            'prop1' => '6545',
            'prop2' => 'asd',
            'prop3' => true,
            'prop4' => [
                'prop1' => '123,asd,true,123.3423',
                'prop2' => 'zxc',
                'prop3' => '1',
            ],
        ];
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new RecursiveLoader($factory, Configuration::DEFAULT_GROUP_NAME, true);
        $obj = new TestBag3();
        $errors = $loader->load($obj, $properties);
        
        $this->assertSame(
            [
                'prop2' => ['TYPE_EXCEPTION' => "The type of the variable in the method NewInventor\\Transformers\\Transformer\\ToInt->validateInputTypes is incorrect.\nRequired type is: numeric \nType received: string",],
                'prop4' => [
                    'prop1' => ['TRANSFORMATION_EXCEPTION' => "Transformation failed: Transformer NewInventor\\Transformers\\Transformer\\InnerTransformer can not normalize value.\nType exception in element 1: \nThe type of the variable in the method NewInventor\\Transformers\\Transformer\\ToInt->validateInputTypes is incorrect.\nRequired type is: numeric \nType received: string",],
                    'prop2' => ['TYPE_EXCEPTION' => "The type of the variable in the method NewInventor\\Transformers\\Transformer\\ToInt->validateInputTypes is incorrect.\nRequired type is: numeric \nType received: string",],
                ],
            ],
            $errors
        );
    }
    
    public function test2()
    {
        $properties = [
            'prop1' => '6545',
            'prop2' => '123',
            'prop3' => true,
            'prop4' => [
                [
                    'prop1' => '123,456,true,123.3423',
                    'prop2' => '3',
                    'prop3' => '1',
                ],
                [
                    'prop1' => '123,456,true,123.3423',
                    'prop2' => '3',
                    'prop3' => '1',
                ],
            ],
        ];
    
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new RecursiveLoader($factory, Configuration::DEFAULT_GROUP_NAME, false);
        $obj = new TestBag5();
        $loader->load($obj, $properties);
        $this->assertCount(2, $obj->getProp4());
    }
    
    public function test3()
    {
        $properties = [
            'prop1' => '6545',
            'prop2' => '123',
            'prop3' => true,
            'prop4' => [
                [
                    'prop1' => '123,456,true,123.3423',
                    'prop2' => '3',
                    'prop3' => '1',
                ],
                'asdasd',
            ],
        ];
    
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new RecursiveLoader($factory, Configuration::DEFAULT_GROUP_NAME, true);
        $obj = new TestBag5();
        $errors = $loader->load($obj, $properties);
        $this->assertSame(
            [
                'prop4' => [
                    1 => [
                        'TYPE_EXCEPTION' => "The type of the variable in the method NewInventor\DataStructure\RecursiveLoader->getNestedException is incorrect.\nRequired types are: null, array \nType received: string",
                    ],
                ],
            ],
            $errors
        );
    }
    
    public function test4()
    {
        $properties = [
            'prop1' => '6545',
            'prop2' => '123',
            'prop3' => true,
        ];
        $factory = new Factory(__DIR__ . '/data', 'TestsDataStructure');
        $loader = new RecursiveLoader($factory, Configuration::DEFAULT_GROUP_NAME, false);
        $obj = new TestBag5();
        $loader->load($obj, $properties);
        $this->assertNull($obj->getProp4());
    }
}