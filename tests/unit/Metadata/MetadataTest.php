<?php
namespace Metadata;

use Codeception\Test\Unit;
use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\Configuration\Parser\Yaml;
use NewInventor\DataStructure\Metadata\Loader;
use NewInventor\DataStructure\Metadata\Metadata;
use NewInventor\Transformers\Transformer\ArrayToCsvString;
use NewInventor\Transformers\Transformer\BoolToMixed;
use NewInventor\Transformers\Transformer\ChainTransformer;
use NewInventor\Transformers\Transformer\StringToDateTime;
use NewInventor\Transformers\Transformer\ToArray;
use NewInventor\Transformers\Transformer\ToBool;
use NewInventor\Transformers\Transformer\ToInt;
use NewInventor\Transformers\Transformer\ToString;
use NewInventor\Transformers\TransformerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use TestsDataStructure\TestBag;
use TestsDataStructure\TestBag1;
use TestsDataStructure\TestBag2;
use TestsDataStructure\TestBag2Bad;

class MetadataTest extends Unit
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
    
    public function test()
    {
        $meta = new Metadata(TestBag::class);
        $parser = new Yaml(new Configuration());
        $loader = new Loader(dirname(__DIR__) . '/data', $parser, 'TestsDataStructure');
        $loader->load($meta);
        $this->assertSame('TestsDataStructure', $meta->getNamespace());
        $this->assertSame('TestBag', $meta->getClassName());
        $this->assertSame('TestsDataStructure\TestBag', $meta->getFullClassName());
        $transformers = $meta->getTransformer();
        $this->assertNull($transformers->getTransformer('prop0'));
        $this->assertSame(ToInt::class, get_class($transformers->getTransformer('prop1')));
        $this->assertSame(ToBool::class, get_class($transformers->getTransformer('prop3')));
        $this->assertNull($transformers->getTransformer('prop4'));
        $this->assertNull($transformers->getTransformer('prop5'));
        $this->assertNull($transformers->getTransformer('prop6'));
        $this->assertNull($transformers->getTransformer('prop8'));
        $this->assertSame(ToInt::class, get_class($transformers->getTransformer('prop2')));
        $this->assertSame(ToString::class, get_class($transformers->getTransformer('prop7')));
        $this->assertNull($transformers->getTransformer('prop9'));
        $transformers = $meta->getTransformer('forward');
        $this->assertNull($transformers->getTransformer('prop0'));
        $this->assertNull($transformers->getTransformer('prop1'));
        $this->assertNull($transformers->getTransformer('prop2'));
        $this->assertNull($transformers->getTransformer('prop9'));
        $this->assertNull($transformers->getTransformer('prop3'));
        $this->assertSame(ToBool::class, get_class($transformers->getTransformer('prop4')));
        $this->assertSame(ToBool::class, get_class($transformers->getTransformer('prop5')));
        $this->assertSame(ChainTransformer::class, get_class($transformers->getTransformer('prop6')));
        $this->assertSame(StringToDateTime::class, get_class($transformers->getTransformer('prop7')));
        $this->assertSame(ToArray::class, get_class($transformers->getTransformer('prop8')));
        $transformers = $meta->getTransformer('backward');
        $this->assertNull($transformers->getTransformer('prop0'));
        $this->assertNull($transformers->getTransformer('prop1'));
        $this->assertNull($transformers->getTransformer('prop2'));
        $this->assertNull($transformers->getTransformer('prop3'));
        $this->assertSame(BoolToMixed::class, get_class($transformers->getTransformer('prop4')));
        $this->assertSame(BoolToMixed::class, get_class($transformers->getTransformer('prop5')));
        $this->assertSame(ArrayToCsvString::class, get_class($transformers->getTransformer('prop6')));
        $this->assertNull($transformers->getTransformer('prop7'));
        $this->assertNull($transformers->getTransformer('prop8'));
        $this->assertNull($transformers->getTransformer('prop9'));
    
        $this->assertSame(
            [
                'prop1' => null,
                'prop2' => null,
                'prop3' => null,
                'prop4' => null,
                'prop5' => null,
                'prop6' => null,
                'prop7' => 2222,
                'prop8' => null,
                'prop9' => null,
            ],
            $meta->properties
        );
        
        $params = [
            'prop1' => '6545',
            'prop2' => '123',
            'prop3' => '04.05.2017',
            'prop9' => [
                'prop1' => '123,456,true,123.3423',
                'prop2' => '3',
                'prop3' => '1',
            ],
        ];
    
        $metadata = new Metadata(TestBag::class);
        $loader->load($metadata);
        $transformer = $metadata->getTransformer();
        $metadata1 = new Metadata(TestBag1::class);
        $loader->load($metadata1);
        /** @var TransformerInterface $transformer1 */
        $transformer1 = $metadata1->getTransformer();
        $params = $transformer->transform($params, true);
        $params['prop9'] = $transformer1->transform($params['prop9'], true);
        $this->assertSame(
            [
                'prop1' => 6545,
                'prop2' => 123,
                'prop3' => true,
                'prop9' => [
                    'prop1' => [
                        123,
                        456,
                        true,
                        123.3423,
                    ],
                    'prop2' => 3,
                    'prop3' => true,
                ],
            ],
            $params
        );
    }
    
    public function test1()
    {
        $this->expectException(InvalidConfigurationException::class);
        $meta = new Metadata(TestBag2Bad::class);
        $parser = new Yaml(new Configuration());
        $loader = new Loader(dirname(__DIR__) . '/data', $parser, 'TestsDataStructure');
        $loader->load($meta);
    }
    
    public function test2()
    {
        $meta = new Metadata(TestBag2::class);
        $parser = new Yaml(new Configuration());
        $loader = new Loader(dirname(__DIR__) . '/data', $parser, 'TestsDataStructure');
        $loader->load($meta);
        $this->assertSame('TestsDataStructure', $meta->getNamespace());
        $this->assertSame('TestBag2', $meta->getClassName());
        $this->assertSame('TestsDataStructure\TestBag2', $meta->getFullClassName());
        $transformers = $meta->getTransformer();
        $this->assertNull($transformers->getTransformer('prop0'));
        $this->assertSame(ToInt::class, get_class($transformers->getTransformer('prop1')));
        $this->assertSame(ToBool::class, get_class($transformers->getTransformer('prop3')));
        $this->assertNull($transformers->getTransformer('prop4'));
        $this->assertNull($transformers->getTransformer('prop5'));
        $this->assertNull($transformers->getTransformer('prop6'));
        $this->assertNull($transformers->getTransformer('prop8'));
        $this->assertSame(ToInt::class, get_class($transformers->getTransformer('prop2')));
        $this->assertNull($transformers->getTransformer('prop7'));
        $this->assertNull($transformers->getTransformer('prop9'));
        $transformers = $meta->getTransformer('forward');
        $this->assertNull($transformers->getTransformer('prop0'));
        $this->assertNull($transformers->getTransformer('prop1'));
        $this->assertNull($transformers->getTransformer('prop2'));
        $this->assertNull($transformers->getTransformer('prop9'));
        $this->assertNull($transformers->getTransformer('prop3'));
        $this->assertSame(ToBool::class, get_class($transformers->getTransformer('prop4')));
        $this->assertSame(ToBool::class, get_class($transformers->getTransformer('prop5')));
        $this->assertSame(ChainTransformer::class, get_class($transformers->getTransformer('prop6')));
        $this->assertSame(ChainTransformer::class, get_class($transformers->getTransformer('prop7')));
        $this->assertSame(ToArray::class, get_class($transformers->getTransformer('prop8')));
        $transformers = $meta->getTransformer('backward');
        $this->assertNull($transformers->getTransformer('prop0'));
        $this->assertNull($transformers->getTransformer('prop1'));
        $this->assertNull($transformers->getTransformer('prop2'));
        $this->assertNull($transformers->getTransformer('prop3'));
        $this->assertSame(BoolToMixed::class, get_class($transformers->getTransformer('prop4')));
        $this->assertSame(BoolToMixed::class, get_class($transformers->getTransformer('prop5')));
        $this->assertSame(ArrayToCsvString::class, get_class($transformers->getTransformer('prop6')));
        $this->assertNull($transformers->getTransformer('prop7'));
        $this->assertNull($transformers->getTransformer('prop8'));
        $this->assertNull($transformers->getTransformer('prop9'));
        
        $this->assertSame(
            [
                'prop1' => null,
                'prop2' => null,
                'prop3' => null,
                'prop4' => null,
                'prop5' => null,
                'prop6' => null,
                'prop7' => 2222,
                'prop8' => null,
                'prop9' => null,
            ],
            $meta->properties
        );
    }
}