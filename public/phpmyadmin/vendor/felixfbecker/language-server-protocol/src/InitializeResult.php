<?php

namespace LanguageServerProtocol;

class InitializeResult
{
    /**
     * The capabilities the language server provides.
     *
     * @var ServerCapabilities
     */
    public $capabilities;

    /**
     * Information about the server.
     *
     * @since 3.15.0
     *
     * @var InitializeResultServerInfo|null
     */
    public $serverInfo;

    public function __construct(ServerCapabilities $capabilities = null, InitializeResultServerInfo $serverInfo = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->capabilities = $capabilities;
        $this->serverInfo = $serverInfo;
    }
}
