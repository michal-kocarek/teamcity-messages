<?php

namespace MichalKocarek\TeamcityMessages\Tests\Writers;

use MichalKocarek\TeamcityMessages\Writers\CallbackWriter;
use PHPUnit_Framework_TestCase;

require_once(__DIR__.'/DataProvider.php');

class CallbackWriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderWrite
     * @param array $messages
     */
    public function testWrite(...$messages)
    {
        $result = '';
        $writer = new CallbackWriter(function($message) use(&$result) {
            $result .= $message;
        });

        foreach($messages as $message) {
            $writer->write($message);
        }

        $expected = implode($messages);
        self::assertSame($expected, $result);
    }

    public function dataProviderWrite()
    {
        return DataProvider::getMessages();
    }
}
