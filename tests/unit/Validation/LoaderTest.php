<?php
namespace Validation;


use NewInventor\DataStructure\Configuration\Configuration;
use NewInventor\DataStructure\Configuration\Parser\Yaml;
use NewInventor\DataStructure\Validation\Loader;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use TestsDataStructure\TestBag;

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
        $loader = new Loader(dirname(__DIR__) . '/data', $parser, 'TestsDataStructure');
        $metadata1 = new ClassMetadata(TestBag::class);
        $this->assertTrue($loader->loadClassMetadata($metadata1));
        $this->assertSame('TestBag', $metadata1->defaultGroup);
        $this->assertNotEmpty($metadata1->members);
        $this->assertNotEmpty($metadata1->properties);
        $this->assertNotEmpty($metadata1->getters);
        $this->assertNotEmpty($metadata1->constraints);
        $this->assertNotEmpty($metadata1->constraintsByGroup);
        $this->assertEmpty($metadata1->groupSequence);
        $this->assertEmpty($metadata1->groupSequenceProvider);
    }
}