<?php
namespace Metadata;


use NewInventor\DataStructure\Metadata\Configuration;
use NewInventor\DataStructure\Metadata\Loader;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Filesystem\Filesystem;

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
        $loader = new Loader(dirname(__DIR__) . '/data', 'TestsDataStructure');
        $cache = new FilesystemAdapter('', 0, dirname(__DIR__) . '/var');
        $loader->setCacheDriver($cache);
        $this->assertSame(dirname(__DIR__) . '/data', $loader->getPath());
        $this->assertSame(dirname(__DIR__) . '/data/TestBag.yml', $loader->getFilePath('TestsDataStructure\TestBag'));
        $this->assertSame('TestsDataStructure', $loader->getBaseNamespace());
        $configuration = new Configuration();
        $loader->loadMetadataFor('TestsDataStructure\TestBag', $configuration);
        $this->assertTrue(is_dir(dirname(__DIR__) . '/var'));
        $loader->loadMetadataFor('TestsDataStructure\TestBag', $configuration);
        $fileSystem = new Filesystem();
        $fileSystem->remove(dirname(__DIR__) . '/var');
        $this->expectException(\InvalidArgumentException::class);
        $loader->loadMetadataFor('not\existing\class', $configuration);
    }
    
    public function test1()
    {
        $configuration = new Configuration();
        $this->expectException(\RuntimeException::class);
        $loader = new Loader(dirname(__DIR__) . '/data/TestBag.yml');
        $loader->loadMetadataFor('TestBag', $configuration);
    }
    
    public function test2()
    {
        $configuration = new Configuration();
        $this->expectException(\RuntimeException::class);
        $loader = new Loader(dirname(__DIR__) . '/data', 'TestsDataStructure');
        $loader->loadMetadata($configuration);
    }
    
    public function test3()
    {
        $configuration = new Configuration();
        $loader = new Loader(dirname(__DIR__) . '/data/TestBag.yml');
        $loader->loadMetadata($configuration);
        $this->assertSame(dirname(__DIR__) . '/data/TestBag.yml', $loader->getPath());
    }
    
    public function test4()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Loader(dirname(__DIR__) . '/data/TestBag.yml', '', 'Not\existing\class');
    }
    
    public function test5()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Loader(dirname(__DIR__) . '/data/not/exist');
    }
}