<?php

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
        $transformer->transform($props);
        $this->assertSame(
            ['prop1' => ['TYPE_EXCEPTION' => ['TO_INT' => 'Type of value invalid.']]],
            $transformer->getErrors()
        );
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
        $transformer->transform($props);
        $this->assertSame(
            ['prop1' => ['TRANSFORMATION_EXCEPTION' => ['STRING_TO_DATE_TIME' => 'Date format invalid. (must be \'d.m.Y\')']]],
            $transformer->getErrors()
        );
    }
    
    public function test3()
    {
        $propertiesTransformers = [
            'prop1' => Transformer\ChainTransformer::make(
                Transformer\StringToDateTime::make('d.m.Y'),
                Transformer\DateTimeToString::make('d.m.Y'),
                Transformer\StringToPhone::make(),
                Transformer\ArrayToCsvString::make()
            ),
            'prop2' => Transformer\ToInt::make(),
            'prop3' => Transformer\InnerTransformer::make(
                Transformer\StringToDateTime::make('d.m.Y'),
                Transformer\DateTimeToString::make('d.m.Y'),
                Transformer\StringToPhone::make(),
                Transformer\ArrayToCsvString::make()
            ),
        ];
        
        $props = [
            'prop1' => 'qwe',
            'prop2' => 'qwe',
            'prop3' => [
                'a123',
                'qwe',
                '7-987-654-32-12',
                1,
            ],
        ];
        
        $transformer = new PropertiesTransformer($propertiesTransformers);
        $res = $transformer->transform($props);
        $this->assertSame(
            [
                'prop1' => 'qwe',
                'prop2' => 'qwe',
                'prop3' => [
                    'a123',
                    'qwe',
                    '7-987-654-32-12',
                    1,
                ],
            ],
            $res
        );
        $this->assertEquals(
            [
                'prop1' => [
                    'TRANSFORMATION_CONTAINER_EXCEPTION' =>
                        [
                            'STRING_TO_DATE_TIME' => [
                                'TRANSFORMATION_EXCEPTION' => ['STRING_TO_DATE_TIME' => 'Date format invalid. (must be \'d.m.Y\')'],
                            ],
                            'DATE_TIME_TO_STRING' => [
                                'TYPE_EXCEPTION' => ['DATE_TIME_TO_STRING' => 'Type of value invalid.'],
                            ],
                            'STRING_TO_PHONE'     => [
                                'TRANSFORMATION_EXCEPTION' => ['STRING_TO_PHONE' => 'Phone should be string with 11 numbers'],
                            ],
                            'ARRAY_TO_CSV_STRING' => [
                                'TYPE_EXCEPTION' => ['ARRAY_TO_CSV_STRING' => 'Type of value invalid.'],
                            ],
                        ],
                ],
                'prop2' => ['TYPE_EXCEPTION' => ['TO_INT' => 'Type of value invalid.']],
                'prop3' => [
                    0 => [
                        'TRANSFORMATION_EXCEPTION' => ['STRING_TO_DATE_TIME' => 'Date format invalid. (must be \'d.m.Y\')'],
                    ],
                    1 => [
                        'TYPE_EXCEPTION' => ['DATE_TIME_TO_STRING' => 'Type of value invalid.'],
                    ],
                    3 => [
                        'TYPE_EXCEPTION' => ['ARRAY_TO_CSV_STRING' => 'Type of value invalid.'],
                    ],
                ],
            ],
            $transformer->getErrors()
        );
    }
}