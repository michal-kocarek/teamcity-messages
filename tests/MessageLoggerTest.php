<?php

namespace Bileto\TeamcityMessages\Tests;

use Bileto\TeamcityMessages\MessageLogger;
use Bileto\TeamcityMessages\Writers\StdoutWriter;
use PHPUnit_Framework_TestCase;

class MessageLoggerTest extends PHPUnit_Framework_TestCase
{

    public function testDerive()
    {
        $logger = new MessageLogger(new StdoutWriter(), 1234);
        $newLogger = $logger->derive();

        self::assertSame($logger->getWriter(), $newLogger->getWriter());
        self::assertSame(null, $newLogger->getFlowId());
    }

    /**
     * @param callable $callback
     * @param $expected
     * @dataProvider dataProviderLogging
     */
    public function testLogging(callable $callback, $expected)
    {
        $logger = new MessageLogger(new StdoutWriter());

        ob_start();
        $callback($logger);
        $dump = ob_get_clean();

        // Normalize dynamic timestamp
        $dump = preg_replace("/timestamp='[^']+'/", "timestamp='*'", $dump);

        // Normalize expected
        $expected .= PHP_EOL;

        self::assertSame($expected, $dump);
    }

    public function dataProviderLogging()
    {
        return [
            'message' => [
                function(MessageLogger $logger) {
                    $logger->message('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='NORMAL']",
            ],
            'warning' => [
                function(MessageLogger $logger) {
                    $logger->warning('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='WARNING']",
            ],
            'failure' => [
                function(MessageLogger $logger) {
                    $logger->failure('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='FAILURE']",
            ],
            'error' => [
                function(MessageLogger $logger) {
                    $logger->error('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='ERROR']",
            ],
            'error-with-details' => [
                function(MessageLogger $logger) {
                    $logger->error('Foo', 'some-stacktrace');
                },
                "##teamcity[message timestamp='*' text='Foo' status='ERROR' errorDetails='some-stacktrace']",
            ],
            'block-opened' => [
                function(MessageLogger $logger) {
                    $logger->blockOpened('Foo');
                },
                "##teamcity[blockOpened timestamp='*' name='Foo']",
            ],
            'block-opened-with-description' => [
                function(MessageLogger $logger) {
                    $logger->blockOpened('Foo', 'description');
                },
                "##teamcity[blockOpened timestamp='*' name='Foo' description='description']",
            ],
            'block-closed' => [
                function(MessageLogger $logger) {
                    $logger->blockClosed('Foo');
                },
                "##teamcity[blockClosed timestamp='*' name='Foo']",
            ],
            'compilation-started' => [
                function(MessageLogger $logger) {
                    $logger->compilationStarted('Foo');
                },
                "##teamcity[compilationStarted timestamp='*' compilerName='Foo']",
            ],
            'compilation-finished' => [
                function(MessageLogger $logger) {
                    $logger->compilationFinished('Foo');
                },
                "##teamcity[compilationFinished timestamp='*' compilerName='Foo']",
            ],
            'publish-artifacts' => [
                function(MessageLogger $logger) {
                    $logger->publishArtifacts('a/path');
                },
                "##teamcity[publishArtifacts timestamp='*' path='a/path']",
            ],
            'progress-message' => [
                function(MessageLogger $logger) {
                    $logger->progressMessage('Foo');
                },
                "##teamcity[progressMessage timestamp='*' message='Foo']",
            ],
            'progress-start' => [
                function(MessageLogger $logger) {
                    $logger->progressStart('Foo');
                },
                "##teamcity[progressStart timestamp='*' message='Foo']",
            ],
            'progress-finish' => [
                function(MessageLogger $logger) {
                    $logger->progressFinish('Foo');
                },
                "##teamcity[progressFinish timestamp='*' message='Foo']
                ",
            ],
//            '' => [
//                function(MessageLogger $logger) {
//                    $logger->...('Foo');
//                },
//                "",
//            ],
        ];
    }

}
