<?php

use NewInventor\DataStructure\Exception\PropertyInvalidTypeException;
use NewInventor\DataStructure\Exception\PropertyTransformationException;
use NewInventor\DataStructure\PropertiesTransformer;
use NewInventor\Transformers\Transformer;

class ProperteisTransformerTest extends \Codeception\Test\Unit
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
        $propertiesTransformers = [
            'prop1' => Transformer\ToInt::make(),
            'prop2' => Transformer\ToBool::make(['true']),
            'prop3' => Transformer\ToFloat::make(),
        ];
        
        $props = [
            'prop1' => '123',
            'prop2' => 'true',
            'prop3' => '123.321',
        ];
        
        $transformer = new PropertiesTransformer($propertiesTransformers);
        $res = $transformer->transform($props);
        $this->assertSame(
            [
                'prop1' => 123,
                'prop2' => true,
                'prop3' => 123.321,
            ],
            $res
        );
        
        $props['qwe'] = 'qweasdzxc,123,3232.3';
        $res = $transformer->transform($props);
        $this->assertSame(
            [
                'prop1' => 123,
                'prop2' => true,
                'prop3' => 123.321,
                'qwe'   => 'qweasdzxc,123,3232.3',
            ],
            $res
        );
        $transformer->setTransformer(
            'qwe',
            new Transformer\ChainTransformer(
                new Transformer\CsvStringToArray(),
                new Transformer\InnerTransformer(
                    new Transformer\ToString(),
                    new Transformer\ToInt(),
                    new Transformer\ToFloat()
                )
            )
        );
        
        $this->assertSame(Transformer\ChainTransformer::class, get_class($transformer->getTransformer('qwe')));
        $this->assertSame(
            [
                'prop1' => 123,
                'prop2' => true,
                'prop3' => 123.321,
                'qwe'   => ['qweasdzxc', 123, 3232.3],
            ],
            $transformer->transform($props)
        );
        
        $props['asd'] = '1';
        $props['zxc'] = 'false';
        $transformer->setTransformers(
            ['asd' => new Transformer\ToInt(), 'zxc' => new Transformer\ToBool([], ['false'])]
        );
        $this->assertSame(Transformer\ToInt::class, get_class($transformer->getTransformer('asd')));
        $this->assertSame(Transformer\ToBool::class, get_class($transformer->getTransformer('zxc')));
    }
    
    public function test1()
    {
        $propertiesTransformers = [
            'prop1' => Transformer\ToInt::make(),
        ];
        
        $props = [
            'prop1' => 'qwe',
        ];
        
        $transformer = new PropertiesTransformer($propertiesTransformers);
        $this->expectException(PropertyInvalidTypeException::class);
        $transformer->transform($props);
    }
    
    public function test2()
    {
        $propertiesTransformers = [
            'prop1' => Transformer\StringToDateTime::make('d.m.Y'),
        ];
        
        $props = [
            'prop1' => 'qwe',
        ];
        
        $transformer = new PropertiesTransformer($propertiesTransformers);
        $this->expectException(PropertyTransformationException::class);
        $transformer->transform($props);
    }
    
    public function test3()
    {
        $propertiesTransformers = [
            'prop1' => Transformer\StringToDateTime::make('d.m.Y'),
            'prop2' => Transformer\ToInt::make(),
        ];
        
        $props = [
            'prop1' => 'qwe',
            'prop2' => 'qwe',
        ];
        
        $transformer = new PropertiesTransformer($propertiesTransformers);
        $res = $transformer->transform($props, true);
        $this->assertSame(
            [
                'prop1' => 'qwe',
                'prop2' => 'qwe',
            ],
            $res
        );
        $this->assertEquals(
            [
                'prop1' => ['TRANSFORMATION_EXCEPTION' => 'Date format invalid. (must be \'d.m.Y\')'],
                'prop2' => ['TYPE_EXCEPTION' => "The type of the variable in the method NewInventor\\Transformers\\Transformer\\ToInt->validateInputTypes is incorrect.\nRequired type is: numeric \nType received: string"],
            ],
            $transformer->getErrors()
        );
    }
}