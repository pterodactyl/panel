<?php

namespace LanguageServerProtocol;

class CodeActionClientCapabilitiesCodeActionLiteralSupportcodeActionKind
{

    /**
     * The code action kind values the client supports. When this
     * property exists the client also guarantees that it will
     * handle values outside its set gracefully and falls back
     * to a default value when unknown.
     *
     * @var string[]
     * @see CodeActionKind
     */
    public $valueSet;

    /**
     * Undocumented function
     *
     * @param string[] $valueSet
     */
    public function __construct(array $valueSet)
    {
        $this->valueSet = $valueSet;
    }
}
