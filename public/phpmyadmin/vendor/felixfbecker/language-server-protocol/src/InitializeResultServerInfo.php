<?php

namespace LanguageServerProtocol;

class InitializeResultServerInfo
{
    /**
     * The name of the server as defined by the server.
     *
     * @var string
     */
    public $name;

    /**
     * The server's version as defined by the server.
     *
     * @var string|null
     */
    public $version;

    public function __construct(string $name, string $version = null)
    {
        $this->name = $name;
        $this->version = $version;
    }
}
