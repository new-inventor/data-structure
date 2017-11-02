<?php
/**
 * Project: data-structure
 * User: george
 * Date: 02.11.17
 */

namespace NewInventor\DataStructure\Configuration\Parser;


use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml as SymfonyYml;

class Yaml implements ParserInterface
{
    /** @var ConfigurationInterface */
    protected $configuration;
    
    /**
     * YamlConfigParser constructor.
     *
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }
    
    /**
     * @param                   $file
     *
     * @return array
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function parse($file): array
    {
        $config = $this->readConfigFileToArray($file);
        $processor = new Processor();
        
        return $processor->processConfiguration($this->configuration, [$config]);
    }
    
    /**
     * @param string $file
     *
     * @return array
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    protected function readConfigFileToArray(string $file): array
    {
        return SymfonyYml::parse(file_get_contents($file));
    }
}