<?php

namespace MichalKocarek\TeamcityMessages\Tests\Writers;

use MichalKocarek\TeamcityMessages\Writers\StdoutWriter;
use PHPUnit_Framework_TestCase;

class StdoutWriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderWrite
     * @param array $messages
     */
    public function testWrite(...$messages)
    {
        $expected = implode($messages);

        $writer = new StdoutWriter();

        ob_start();
        foreach($messages as $message) {
            $writer->write($message);
        }

        $result = ob_get_clean();

        self::assertSame($expected, $result);
    }

    public function dataProviderWrite()
    {
        return [
            [''],
            ['a message'],
            ['a message'.PHP_EOL, 'another'.PHP_EOL],
        ];
    }
}
