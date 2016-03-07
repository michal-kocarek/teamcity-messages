<?php

namespace MichalKocarek\TeamcityMessages\Writers;

/**
 * Contract defines method {@link Writer::write()} that is capable
 * to take any message and writes it somewhere.
 */
interface Writer
{
    /**
     * Writes a message.
     *
     * Method _SHOULD NOT_ perform any message post-processing
     * and _SHOULD_ accept any contents as a message.
     *
     * The message _SHOULD_ end with `PHP_EOL`.
     *
     * @param string $message The message.
     * @return void
     */
    public function write($message);
}
