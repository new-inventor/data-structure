<?php
/**
 * Project: property-bag
 * User: george
 * Date: 06.10.17
 */

namespace TestsDataStructure;


class TestStatic
{
    const AAA = 0;
    public static $bbb = 1;
    
    public static function GetTrue(): array
    {
        return [
            'true',
            1,
        ];
    }
}