<?php

namespace LanguageServerProtocol;

/**
 * A notification to log the trace of the server’s execution.
 * The amount and content of these notifications depends on the
 * current trace configuration. If trace is 'off', the server
 * should not send any logTrace notification. If trace is
 * 'messages', the server should not add the 'verbose' field in
 * the LogTraceParams.
 *
 * $/logTrace should be used for systematic trace reporting.
 * For single debugging messages, the server should send
 * window/logMessage notifications.
 */
class LogTrace
{
    /**
     * The message to be logged.
     *
     * @var string
     */
    public $message;

    /**
     * Additional information that can be computed if the `trace` configuration
     * is set to `'verbose'`
     *
     * @var string|null
     */
    public $verbose;

    public function __construct(string $message, string $verbose = null)
    {
        $this->message = $message;
        $this->verbose = $verbose;
    }
}
