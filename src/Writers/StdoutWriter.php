<?php

namespace Bileto\TeamcityMessages\Writers;

/**
 * Instance echoes messages to standard output.
 */
class StdoutWriter implements Writer
{
    /**
     * Writes a message to standard output.
     *
     * @param string $message The message.
     * @return void
     */
    public function write($message)
    {
        echo $message;
    }
}
