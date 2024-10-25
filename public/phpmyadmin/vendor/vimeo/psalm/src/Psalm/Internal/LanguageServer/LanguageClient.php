<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use JsonMapper;
use Psalm\Internal\LanguageServer\Client\TextDocument as ClientTextDocument;

/**
 * @internal
 */
class LanguageClient
{
    /**
     * Handles textDocument/* methods
     *
     * @var ClientTextDocument
     */
    public $textDocument;

    /**
     * The client handler
     *
     * @var ClientHandler
     */
    private $handler;

    public function __construct(ProtocolReader $reader, ProtocolWriter $writer)
    {
        $this->handler = new ClientHandler($reader, $writer);
        $mapper = new JsonMapper;

        $this->textDocument = new ClientTextDocument($this->handler, $mapper);
    }

    /**
     * Send a log message to the client.
     *
     * @param string $message The message to send to the client.
     * @psalm-param 1|2|3|4 $type
     * @param int $type The log type:
     *  - 1 = Error
     *  - 2 = Warning
     *  - 3 = Info
     *  - 4 = Log
     */
    public function logMessage(string $message, int $type = 4, string $method = 'window/logMessage'): void
    {
        // https://microsoft.github.io/language-server-protocol/specifications/specification-current/#window_logMessage

        if ($type < 1 || $type > 4) {
            $type = 4;
        }

        $this->handler->notify(
            $method,
            [
                'type' => $type,
                'message' => $message
            ]
        );
    }
}
