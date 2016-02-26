<?php

namespace Bileto\TeamcityMessages\Tests;

use Bileto\TeamcityMessages\Util;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class UtilTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider formatDataProvider
     * @param string $messageName
     * @param array $properties
     * @param string $expected
     */
    public function testFormat($messageName, $properties, $expected)
    {
        $expected .= PHP_EOL;

        $result = Util::format($messageName, $properties);

        self::assertSame($expected, $result);
    }

    public function formatDataProvider()
    {
        /** @noinspection UnNecessaryDoubleQuotesInspection */
        return [
            'simple' => ['foo', [], "##teamcity[foo]"],
            'simple-params' => ['start', ['bar' => 'baz'], "##teamcity[start bar='baz']"],
            'empty-param' => ['foo', ['bar' => ''], "##teamcity[foo bar='']"],
            'escape-quote' => ['start', ['bar' => ' \' '], "##teamcity[start bar=' |' ']"],
            'escape-nl' => ['start', ['bar' => " \n "], "##teamcity[start bar=' |n ']"],
            'escape-cr' => ['start', ['bar' => " \r "], "##teamcity[start bar=' |r ']"],
            'escape-pipe' => ['start', ['bar' => " | "], "##teamcity[start bar=' || ']"],
            'escape-brackets' => ['start', ['bar' => " [ ] "], "##teamcity[start bar=' |[ |] ']"],
            'escape-unicode' => ['start', ['bar' => ' \u0123 '], "##teamcity[start bar=' |0x0123 ']"],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider dataProviderInvalidJavaId
     * @param string $value
     */
    public function testFormatWithInvalidMessageName($value)
    {
        Util::format($value);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider dataProviderInvalidJavaId
     * @param string $value
     */
    public function testFormatWithInvalidPropertyName($value)
    {
        Util::format('foo', [$value => '']);
    }

    public function dataProviderInvalidJavaId()
    {
        return [
            'empty-string' => [''],
            'space' => [' '],
            'unicode' => ['č'],
        ];
    }
}
