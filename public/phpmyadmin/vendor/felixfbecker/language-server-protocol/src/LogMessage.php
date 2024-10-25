<?php

namespace LanguageServerProtocol;

/**
 * The log message notification is sent from the server to the client to ask the client to log a particular message.
 */
class LogMessage
{
    /**
     * The message type. See {@link MessageType}
     *
     * @var int
     * @see MessageType
     */
    public $type;

    /**
     * The actual message
     *
     * @var string
     */
    public $message;

    public function __construct(int $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }
}
