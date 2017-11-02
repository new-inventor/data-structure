<?php
/**
 * Project: data-structure
 * User: george
 * Date: 12.10.17
 */

namespace NewInventor\DataStructure\Configuration\Parser;


interface ParserInterface
{
    /**
     * @param                   $file
     *
     * @return array
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function parse($file): array;
}