<?php

namespace LanguageServerProtocol;

class CodeActionDisabled
{
    /**
     * Human readable description of why the code action is currently
     * disabled.
     *
     * This is displayed in the code actions UI.
     *
     * @var string
     */
    public $reason;

    public function __construct(string $reason)
    {
        $this->reason = $reason;
    }
}
