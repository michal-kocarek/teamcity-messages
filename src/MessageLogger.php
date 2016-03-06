<?php

namespace Bileto\TeamcityMessages;
use Bileto\TeamcityMessages\Writers\Writer;

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

    // TODO: Implement testSuiteStarted, testSuiteFinished, testStarted, testFinished, testIgnored, testStdOut, testStdErr messages.

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

    public function buildProblem($description, $identity = null)
    {
        
    }
    
    //endregion

    //region Reporting Build Status

    public function buildStatus($status = null, $text = null)
    {
        
    }

    //endregion
    
    //region Reporting Build Number

    public function buildNumber($buildNumber)
    {

    }
    
    //endregion

    //region Adding or Changing a Build Parameter

    /**
     * @param $name
     * @param $value
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-AddingorChangingaBuildParameter
     */
    public function setParameter($name, $value)
    {

    }
    
    //endregion

    //region Reporting Build Statistics

    /**
     * @param $key
     * @param $value
     *
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-ReportingBuildStatistics
     */
    public function buildStatisticValue($key, $value)
    {
        
    }
    
    //endregion

    //region Disabling Service Messages Processing

    public function serviceMessagesEnable()
    {
        
    }

    public function serviceMessagesDisable()
    {
        
    }

    // TODO: Fix that fucking name
    public function withoutServiceMessages(callable $callback)
    {
        
    }
    
    //endregion
    
    //region Importing XML Reports

    public function importData($type, $path)
    {
        // TODO: Tohle bere ještě nějaký argumenty navíc!
        
    }
    
    //endregion

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
