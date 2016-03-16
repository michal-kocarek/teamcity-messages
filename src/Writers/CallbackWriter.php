<?php

namespace MichalKocarek\TeamcityMessages\Writers;

/**
 * Instance passes messages to the callback.
 */
class CallbackWriter implements Writer
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback Callback accepting string as first argument, returning void.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Passes message to the callback.
     *
     * @param string $message The message.
     * @return void
     */
    public function write($message)
    {
        call_user_func($this->callback, $message);
    }
}
