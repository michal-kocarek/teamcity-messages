<?php

namespace MichalKocarek\TeamcityMessages\Tests;

use InvalidArgumentException;
use MichalKocarek\TeamcityMessages\MessageLogger;
use MichalKocarek\TeamcityMessages\Writers\StdoutWriter;
use PHPUnit_Framework_TestCase;

class MessageLoggerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var MessageLogger
     */
    private $logger;

    public function setUp()
    {
        $this->logger = new MessageLogger(new StdoutWriter());
    }

    public function testDerive()
    {
        $logger = new MessageLogger(new StdoutWriter(), 1234);
        $newLogger = $logger->derive();

        self::assertSame($logger->getWriter(), $newLogger->getWriter());
        self::assertNull($newLogger->getFlowId());
    }

    /**
     * @param callable $callback
     * @param $expected
     * @dataProvider dataProviderLogging
     */
    public function testLogging(callable $callback, $expected)
    {
        $logger = $this->logger;

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
                    $logger->logMessage('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='NORMAL']",
            ],
            'warning' => [
                function(MessageLogger $logger) {
                    $logger->logWarning('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='WARNING']",
            ],
            'failure' => [
                function(MessageLogger $logger) {
                    $logger->logFailure('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='FAILURE']",
            ],
            'error' => [
                function(MessageLogger $logger) {
                    $logger->logError('Foo');
                },
                "##teamcity[message timestamp='*' text='Foo' status='ERROR']",
            ],
            'error-with-details' => [
                function(MessageLogger $logger) {
                    $logger->logError('Foo', 'some-stacktrace');
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
                "##teamcity[progressFinish timestamp='*' message='Foo']",
            ],
            'suite-started' => [
                function(MessageLogger $logger) {
                    $logger->testSuiteFinished('Foo');
                },
                "##teamcity[testSuiteFinished timestamp='*' name='Foo']",
            ],
            'suite-finished' => [
                function(MessageLogger $logger) {
                    $logger->testSuiteFinished('Foo');
                },
                "##teamcity[testSuiteFinished timestamp='*' name='Foo']",
            ],
            'test-started' => [
                function(MessageLogger $logger) {
                    $logger->testStarted('Foo', true);
                },
                "##teamcity[testStarted timestamp='*' name='Foo' captureStandardOutput='true']",
            ],
            'test-finished' => [
                function(MessageLogger $logger) {
                    $logger->testFinished('Foo');
                },
                "##teamcity[testFinished timestamp='*' name='Foo']",
            ],
            'test-finished-with-duration' => [
                function(MessageLogger $logger) {
                    $logger->testFinished('Foo', 10.123456789);
                },
                "##teamcity[testFinished timestamp='*' name='Foo' duration='101234568']",
            ],
            'test-failed' => [
                function(MessageLogger $logger) {
                    $logger->testFailed('Foo', 'message', 'details');
                },
                "##teamcity[testFailed timestamp='*' name='Foo' message='message' details='details']",
            ],
            'test-failed-with-comparison' => [
                function(MessageLogger $logger) {
                    $logger->testFailedWithComparison('Foo', 'message', 'details', 'actual', 'expected');
                },
                "##teamcity[testFailed timestamp='*' name='Foo' message='message' type='comparisonFailure' details='details' actual='actual' expected='expected']",
            ],
            'test-ignored' => [
                function(MessageLogger $logger) {
                    $logger->testIgnored('Foo', 'message', 'details');
                },
                "##teamcity[testIgnored timestamp='*' name='Foo' message='message' details='details']",
            ],
            'test-stdout' => [
                function(MessageLogger $logger) {
                    $logger->testStdOut('Foo', 'out');
                },
                "##teamcity[testStdOut timestamp='*' name='Foo' out='out']",
            ],
            'test-stderr' => [
                function(MessageLogger $logger) {
                    $logger->testStdErr('Foo', 'out');
                },
                "##teamcity[testStdErr timestamp='*' name='Foo' out='out']",
            ],
            'build-problem' => [
                function(MessageLogger $logger) {
                    $logger->buildProblem('Foo', 'problem-id');
                },
                "##teamcity[buildProblem timestamp='*' description='Foo' identity='problem-id']",
            ],
            'build-status' => [
                function(MessageLogger $logger) {
                    $logger->buildStatus('Foo', 'bar');
                },
                "##teamcity[buildStatus timestamp='*' status='Foo' text='bar']",
            ],
            'build-number' => [
                function(MessageLogger $logger) {
                    $logger->buildNumber('1234');
                },
                "##teamcity[buildNumber timestamp='*' '1234']",
            ],
            'set-parameter' => [
                function(MessageLogger $logger) {
                    $logger->setParameter('Foo', 'bar');
                },
                "##teamcity[setParameter timestamp='*' name='Foo' value='bar']",
            ],
            'build-statistic-value' => [
                function(MessageLogger $logger) {
                    $logger->buildStatisticValue('Foo', 12345.0001);
                },
                "##teamcity[buildStatisticValue timestamp='*' key='Foo' value='12345.0001']",
            ],
            'disable-service-messages' => [
                function(MessageLogger $logger) {
                    $logger->disableServiceMessages();
                },
                "##teamcity[disableServiceMessages timestamp='*']",
            ],
            'enable-service-messages' => [
                function(MessageLogger $logger) {
                    $logger->enableServiceMessages();
                },
                "##teamcity[enableServiceMessages timestamp='*']",
            ],
            'import-data' => [
                function(MessageLogger $logger) {
                    $logger->importData(
                        MessageLogger::IMPORT_TYPE_TEST_JUNIT,
                        'foo.xml'
                    );
                },
                "##teamcity[importData timestamp='*' type='junit' path='foo.xml']",
            ],
            'import-data-with-arguments' => [
                function(MessageLogger $logger) {
                    $logger->importData(
                        MessageLogger::IMPORT_TYPE_TEST_JUNIT,
                        'foo.xml',
                        true,
                        true,
                        true
                    );
                },
                "##teamcity[importData timestamp='*' type='junit' path='foo.xml' parseOutOfDate='true' whenNoDataPublished='true' verbose='true']",
            ],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBuildStatusWithNoArgumentsThrowsException()
    {
        $this->logger->buildStatus();
    }

}
