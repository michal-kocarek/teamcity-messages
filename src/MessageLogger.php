<?php

namespace MichalKocarek\TeamcityMessages;
use InvalidArgumentException;
use MichalKocarek\TeamcityMessages\Writers\Writer;

/**
 * Instance is able to write TeamCity messages through one of the writers.
 */
class MessageLogger
{
    /**
     * @var string|null
     */
    private $flowId;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @param Writer $writer The writer used to write messages.
     * @param string|null $flowId The flow ID or `null`.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-MessageFlowId Flow ID description
     */
    public function __construct(Writer $writer, $flowId = null)
    {
        $this->flowId = $flowId;
        $this->writer = $writer;
    }

    /**
     * Derive new message logger with same configuration but the Flow ID.
     *
     * Returned instance uses same writer as the original one.
     *
     * @param string|null $flowId The flow ID or `null`.
     * @return self New instance.
     */
    public function derive($flowId = null)
    {
        return new self($this->writer, $flowId);
    }

    /**
     * Returns the writer.
     *
     * @return Writer The writer instance.
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * Returns currently used flow ID
     *
     * @return null|string The flow ID or null when undefined.
     */
    public function getFlowId()
    {
        return $this->flowId;
    }

    //region Logging

    /**
     * Prints normal message.
     *
     * @param string $text The message.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-reportingMessagesForBuildLogReportingMessagesForBuildLog
     */
    public function message($text)
    {
        $this->logMessage($text, 'NORMAL');
    }

    /**
     * Prints warning message.
     *
     * @param string $text The message.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-reportingMessagesForBuildLogReportingMessagesForBuildLog
     */
    public function warning($text)
    {
        $this->logMessage($text, 'WARNING');
    }

    /**
     * Prints failure message.
     *
     * @param string $text The message.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-reportingMessagesForBuildLogReportingMessagesForBuildLog
     */
    public function failure($text)
    {
        $this->logMessage($text, 'FAILURE');
    }

    /**
     * Prints error message.
     *
     * Note that this message fails the build if setting
     * `Fail build if an error message is logged by build runner`
     * is enabled for the build.
     *
     * @param string $text The message.
     * @param string $errorDetails The error details (e.g. stack strace).
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-reportingMessagesForBuildLogReportingMessagesForBuildLog
     */
    public function error($text, $errorDetails = null)
    {
        $this->logMessage($text, 'ERROR', $errorDetails);
    }

    //endregion

    //region Blocks of Service Messages

    /**
     * Prints block "Opened message".
     *
     * Blocks are used to group several messages in the build log.
     *
     * @param string $name The block name.
     * @param string $description The block description. (Since TeamCity 9.1.5.)
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-BlocksofServiceMessages
     */
    public function blockOpened($name, $description = '')
    {
        $this->write('blockOpened', [
            'name' => $name,
            'description' => strlen($description) ? $description : null,
        ]);
    }

    /**
     * Prints block "Closed message".
     *
     * Blocks are used to group several messages in the build log.
     * When you close the block, all inner blocks are closed automatically.
     *
     * @param string $name The block name.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-BlocksofServiceMessages
     */
    public function blockClosed($name)
    {
        $this->write('blockClosed', [
            'name' => $name,
        ]);
    }

    /**
     * Calls callback inside opening and closing message block.
     *
     * @param string $name The block name.
     * @param string $description The block description. (Since TeamCity 9.1.5.)
     * @param callable $callback Callback that is called inside block. First argument passed is this instance.
     * @return mixed The callback return value.
     */
    public function block($name, $description = '', callable $callback)
    {
        $this->blockOpened($name, $description);
        try {
            return $callback($this);
        } finally {
            $this->blockClosed($name);
        }
    }

    //endregion

    //region Reporting Compilation Messages

    /**
     * Prints block "Compilation started".
     *
     * Any message with status ERROR reported between compilationStarted
     * and compilationFinished will be treated as a compilation error.
     *
     * @param string $compilerName Arbitrary name of compiler performing an operation.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-BlocksofServiceMessages
     */
    public function compilationStarted($compilerName)
    {
        $this->write('compilationStarted', [
            'compilerName' => $compilerName,
        ]);
    }

    /**
     * Prints block "Compilation finished".
     *
     * @param string $compilerName Arbitrary name of compiler performing an operation.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-BlocksofServiceMessages
     */
    public function compilationFinished($compilerName)
    {
        $this->write('compilationFinished', [
            'compilerName' => $compilerName,
        ]);
    }

    /**
     * Calls callback inside opening and closing the compilation block.
     *
     * @param string $compilerName Arbitrary name of compiler performing an operation.
     * @param callable $callback Callback that is called inside block. First argument passed is this instance.
     * @return mixed The callback return value.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-reportingCompilationBlocksReportingCompilationMessages
     */
    public function compilation($compilerName, callable $callback)
    {
        $this->compilationStarted($compilerName);
        try {
            return $callback($this);
        } finally {
            $this->compilationFinished($compilerName);
        }
    }
    
    //endregion

    //region Reporting Tests

    /**
     * Report that test suite started.
     *
     * @param string $name The test suite name.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-Interpretingtestnames TeamCity – Interpreting Test Names
     */
    public function testSuiteStarted($name)
    {
        $this->write('testSuiteStarted', [
            'name' => $name,
        ]);
    }

    /**
     * Report that test suite finished.
     *
     * @param string $name The test suite name.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-Interpretingtestnames TeamCity – Interpreting Test Names
     */
    public function testSuiteFinished($name)
    {
        $this->write('testSuiteFinished', [
            'name' => $name,
        ]);
    }

    /**
     * Report that test started.
     *
     * After test start, finish message should be written using {@link testFinished()}.
     *
     * @param string $name The test name.
     * @param bool $captureStandardOutput If true, all the standard output (and standard error) messages are considered as test output.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-Interpretingtestnames TeamCity – Interpreting Test Names
     */
    public function testStarted($name, $captureStandardOutput = false)
    {
        $this->write('testStarted', [
            'name' => $name,
            'captureStandardOutput' => $captureStandardOutput,
        ]);
    }

    /**
     * Report that test started.
     *
     * @param string $name The test name.
     * @param float $duration The test duration in seconds.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-Interpretingtestnames TeamCity – Interpreting Test Names
     */
    public function testFinished($name, $duration = null)
    {
        $this->write('testFinished', [
            'name' => $name,
            'duration' => $duration !== null ? round($duration * 10000000) : null,
        ]);
    }

    /**
     * Report that test has failed.
     *
     * Message should be written inside the {@link testStarted()} and {@link testFinished()} block.
     *
     * Only one testFailed message can appear for a given test name.
     *
     * @param string $name The test name.
     * @param string $message The textual representation of the error.
     * @param string $details The information on the test failure, typically a message and an exception stacktrace.
     */
    public function testFailed($name, $message, $details = null)
    {
        $this->write('testFailed', [
            'name' => $name,
            'message' => $message,
            'details' => $details,
        ]);
    }

    /**
     * Report that test has failed providing the comparison of expected and actual data.
     *
     * Message should be written inside the {@link testStarted()} and {@link testFinished()} block.
     *
     * Only one testFailed message can appear for a given test name.
     *
     * @param string $name The test name.
     * @param string $message The textual representation of the error.
     * @param string $details The information on the test failure, typically a message and an exception stacktrace.
     * @param string $actual The actual value.
     * @param string $expected The expected value.
     */
    public function testFailedWithComparison($name, $message, $details = null, $actual, $expected)
    {
        $this->write('testFailed', [
            'name' => $name,
            'message' => $message,
            'type' => 'comparisonFailure',
            'details' => $details,
            'actual' => $actual,
            'expected' => $expected,
        ]);
    }

    /**
     * Report that test was not run (ignored) by the testing framework.
     *
     * As an exception, message can be reported without the matching testStarted and testFinished messages.
     *
     * @param string $name The test name.
     * @param string $message The textual description of why test was not run.
     * @param string $details The information on the test failure, typically a message and an exception stacktrace.
     */
    public function testIgnored($name, $message, $details = null)
    {
        $this->write('testIgnored', [
            'name' => $name,
            'message' => $message,
            'details' => $details,
        ]);
    }

    /**
     * Report standard output of the test.
     *
     * Message should be written inside the {@link testStarted()} and {@link testFinished()} block.
     *
     * Only one `testStdOut` message can appear for a given test name.
     *
     * @param string $name The test name.
     * @param string $out The output.
     */
    public function testStdOut($name, $out)
    {
        $this->write('testStdOut', [
            'name' => $name,
            'out' => $out,
        ]);
    }

    /**
     * Report error output of the test.
     *
     * Message should be written inside the {@link testStarted()} and {@link testFinished()} block.
     *
     * Only one `testStdErr` message can appear for a given test name.
     *
     * @param string $name The test name.
     * @param string $out The output.
     */
    public function testStdErr($name, $out)
    {
        $this->write('testStdErr', [
            'name' => $name,
            'out' => $out,
        ]);
    }

    //endregion

    //region Publishing Artifacts while the Build is Still in Progress

    /**
     * Public artifacts while build is running.
     *
     * The $path has to adhere to the same rules as the
     * {@link https://confluence.jetbrains.com/display/TCD9/Configuring+General+Settings#ConfiguringGeneralSettings-artifactPaths Build Artifact specification}
     * of the Build Configuration settings.
     *
     * @param string $path Path in same format as "Artifact Path" settings.
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-PublishingArtifactswhiletheBuildisStillinProgress
     */
    public function publishArtifacts($path)
    {
        $this->write('publishArtifacts', [
            'path' => $path,
        ]);
    }

    //endregion

    //region Reporting Build Progress

    /**
     * Write progress message (e.g. to mark long-running parts in a build script).
     *
     * Message will be shown until another progress message occurs or until the next target starts.
     *
     * @param string $message The message.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-ReportingBuildProgress
     */
    public function progressMessage($message)
    {
        $this->write('progressMessage', [
            'message' => $message,
        ]);
    }

    // TODO: PhpDoc for these methods
    public function progressStart($message)
    {
        $this->write('progressStart', [
            'message' => $message,
        ]);
    }

    public function progressFinish($message)
    {
        $this->write('progressFinish', [
            'message' => $message,
        ]);
    }

    public function progress($message, callable $callback)
    {
        $this->progressStart($message);
        try {
            return $callback($this);
        } finally {
            $this->progressFinish($message);
        }
    }
    
    //endregion

    //region Reporting Build Problems

    /**
     * Write "Build Problem" message.
     *
     * Build problems appear on the Build Results page and also affect the build status text.
     *
     * @param string $description The text describing the build problem. Text exceeding 4000 symbols will be truncated.
     * @param string|null $identity The unique problem ID in format of valid Java ID. Identity should be same for same build problems.
     */
    public function buildProblem($description, $identity = null)
    {
        if ($identity !== null) {
            Util::ensureValidJavaId($identity);
        }
        
        $this->write('buildProblem', [
            'description' => $description,
            'identity' => $identity,
        ]);
    }
    
    //endregion

    //region Reporting Build Status

    /**
     * Write "Build Status" message.
     *
     * Allows changing build status text from the build script. You can also change the build status of a failing build to success.
     *
     * Optionally, the text can use `{build.status.text}` substitution pattern which represents the status, calculated by TeamCity automatically using passed test count, compilation messages and so on.
     *
     * @param string $status The status. Only allowed value is: `SUCCESS`.
     * @param string $text The new build status text.
     */
    public function buildStatus($status = null, $text = null)
    {
        if ($status === null && $text === null) {
            throw new InvalidArgumentException('At least one argument must be non-null.');
        }

        $this->write('buildStatus', [
            'status' => $status,
            'text' => $text,
        ]);
    }

    //endregion
    
    //region Reporting Build Number

    /**
     * Set a custom build number directly.
     *
     * You can use the `{build.number}` substitution to use the current build number automatically generated by TeamCity.
     *
     * @param string $buildNumber
     */
    public function buildNumber($buildNumber)
    {
        $this->write('buildNumber', [
            $buildNumber,
        ]);
    }
    
    //endregion

    //region Adding or Changing a Build Parameter

    /**
     * Update build parameter value.
     *
     * Note the parameters need to be defined in the Parameters section of the build configuration.
     *
     * Note some parameters must be specified including their prefix (e.g. `system` or `env`).
     *
     * @param string $name The parameter name.
     * @param string $value The parameter value.
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Configuring+Build+Parameters TeamCity – Configuring Build Parameters
     */
    public function setParameter($name, $value)
    {
        $this->write('setParameter', [
            'name' => $name,
            'value' => $value,
        ]);
    }
    
    //endregion

    //region Reporting Build Statistics

    /**
     * @param string $key The name of custom statistical data.
     * @param int|float $value The value should be a positive/negative integer of up to 13 digits (Since TeamCity 9.0, float values with up to 6 decimal places are also supported.).
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-ReportingBuildStatistics
     */
    public function buildStatisticValue($key, $value)
    {
        $this->write('buildStatisticValue', [
            'key' => $key,
            'value' => $value,
        ]);
    }
    
    //endregion

    //region Disabling Service Messages Processing

    /**
     * Disable parsing of service messages in the output.
     *
     * Parsing is toggled back using {@link enableServiceMessages()}.
     */
    public function disableServiceMessages()
    {
        $this->write('disableServiceMessages', []);
    }

    /**
     * Reenable parsing of service messages in the output.
     *
     * Useful when parsing was disabled using {@link disableServiceMessages()}.
     */
    public function enableServiceMessages()
    {
        $this->write('enableServiceMessages', []);
    }

    /**
     * Disable parsing of service messages emitted from inside a callback.
     *
     * @param callable $callback Callback that is called inside block. First argument passed is this instance.
     * @return mixed The callback return value.
     */
    public function withoutServiceMessages(callable $callback)
    {
        $this->disableServiceMessages();
        try {
            return $callback($this);
        } finally {
            $this->enableServiceMessages();
        }
    }
    
    //endregion
    
    //region Importing XML Reports

    public function importData($type, $path)
    {
        // TODO: Tohle bere ještě nějaký argumenty navíc! – možná rozdělit na více metod podle rozdílných argumentů

        $this->write('importData', [

        ]);
    }
    
    //endregion

    /**
     * @param string $text
     * @param string $status
     * @param null|string $errorDetails
     */
    private function logMessage($text, $status, $errorDetails = null)
    {
        $this->write('message', [
            'text' => $text,
            'status' => $status,
            'errorDetails' => $errorDetails,
        ]);
    }

    /**
     * @param string $messageName
     * @param array $parameters Parameters with value === `null` will be filtered out.
     */
    private function write($messageName, array $parameters)
    {
        /** @noinspection AdditionOperationOnArraysInspection */
        $parameters = [
                'timestamp' => Util::formatTimestamp(),
                'flowId' => $this->flowId,
            ] + $parameters;

        // Filter out optional parameters.
        $parameters = array_filter($parameters, function ($value) {
            return $value !== null;
        });

        $this->writer->write(Util::format($messageName, $parameters));
    }

}
