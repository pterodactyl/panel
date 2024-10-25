<?php

namespace LanguageServerProtocol;

class ExecuteCommandOptions
{

    /**
     * The commands to be executed on the server
     *
     * @var string[]
     */
    public $commands;

    /**
     * @param string[] $commands
     */
    public function __construct(
        array $commands
    ) {
        $this->commands = $commands;
    }
}
