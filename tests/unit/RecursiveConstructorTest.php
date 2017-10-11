<?php

use NewInventor\DataStructure\Metadata\Configuration;
use NewInventor\DataStructure\Metadata\Loader;
use NewInventor\DataStructure\RecursiveConstructor;

class RecursiveConstructorTest extends \Codeception\Test\Unit
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
        $loader = new Loader(__DIR__ . '/data', 'TestsDataStructure');
        $config = new Configuration();
        $constructor = new RecursiveConstructor($loader, $config);
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
        /** @var \TestsDataStructure\TestBag3 $bag */
        $bag = $constructor->construct('TestsDataStructure\TestBag3', $properties);
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
        $loader = new Loader(__DIR__ . '/data', 'TestsDataStructure');
        $config = new Configuration();
        $constructor = new RecursiveConstructor($loader, $config);
        $constructor->setFailOnFirstError(false);
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
        $constructor->construct('TestsDataStructure\TestBag3', $properties);
        $errors = $constructor->getErrors();
        
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
        $loader = new Loader(__DIR__ . '/data', 'TestsDataStructure');
        $config = new Configuration();
        $constructor = new RecursiveConstructor($loader, $config);
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
        /** @var \TestsDataStructure\TestBag5 $bag */
        $bag = $constructor->construct('TestsDataStructure\TestBag5', $properties);
        $this->assertCount(2, $bag->getProp4());
    }
}