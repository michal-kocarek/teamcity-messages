<?php

namespace Bileto\TeamcityMessages\Tests;

use Exception;
use LogicException;
use MichalKocarek\TeamcityMessages\MessageLogger;
use MichalKocarek\TeamcityMessages\Writers\StdoutWriter;
use PHPUnit_Framework_TestCase;

class MessageLoggerCallbacksTest extends PHPUnit_Framework_TestCase
{

    /**
     * @param callable $caller
     * @param string $expected
     * @dataProvider callbacksDataProvider
     */
    public function testCallbacks(callable $caller, $expected)
    {
        $logger = new MessageLogger(new StdoutWriter());

        $callback = function(MessageLogger $passedLogger) use($logger) {
            self::assertSame($logger, $passedLogger);
            return 'foo';
        };

        ob_start();
        $result = $caller($logger, $callback);
        $dump = ob_get_clean();

        self::assertSame('foo', $result);

        $this->assertDump($expected, $dump);
    }

    /**
     * @param callable $caller
     * @param string $expected
     * @dataProvider callbacksDataProvider
     */
    public function testCallbacksRethrowException(callable $caller, $expected)
    {
        $logger = new MessageLogger(new StdoutWriter());

        $raisedException = new LogicException('Foo');

        $callback = function(MessageLogger $passedLogger) use($logger, $raisedException) {
            self::assertSame($logger, $passedLogger);
            throw $raisedException;
        };

        $exception = null;
        $result = null;
        ob_start();
        try {
            $result = $caller($logger, $callback);
        } catch(Exception $ex) {
            $exception = $ex;
        }
        $dump = ob_get_clean();

        self::assertSame($raisedException, $exception);
        self::assertNull($result);

        $this->assertDump($expected, $dump);
    }

    public function callbacksDataProvider()
    {
        return [
            'block' => [
                function(MessageLogger $logger, callable $callback) {
                    return $logger->block('Foo', 'bar', $callback);
                },
                "##teamcity[blockOpened timestamp='*' name='Foo' description='bar']" . PHP_EOL .
                "##teamcity[blockClosed timestamp='*' name='Foo']"
            ],
            'compilation' => [
                function(MessageLogger $logger, callable $callback) {
                    return $logger->compilation('gcc', $callback);
                },
                "##teamcity[compilationStarted timestamp='*' compilerName='gcc']" . PHP_EOL .
                "##teamcity[compilationFinished timestamp='*' compilerName='gcc']"
            ],
            'progress' => [
                function(MessageLogger $logger, callable $callback) {
                    return $logger->progress('Foo', $callback);
                },
                "##teamcity[progressStart timestamp='*' message='Foo']" . PHP_EOL .
                "##teamcity[progressFinish timestamp='*' message='Foo']"
            ],
            'without-service-messages' => [
                function(MessageLogger $logger, callable $callback) {
                    return $logger->withoutServiceMessages($callback);
                },
                "##teamcity[disableServiceMessages timestamp='*']" . PHP_EOL .
                "##teamcity[enableServiceMessages timestamp='*']"
            ]
        ];
    }

    /**
     * @param $expected
     * @param $dump
     */
    private function assertDump($expected, $dump)
    {
        // Normalize dynamic timestamp
        $dump = preg_replace("/timestamp='[^']+'/", "timestamp='*'", $dump);

        // Normalize expected
        $expected .= PHP_EOL;

        self::assertSame($expected, $dump);
    }

}
