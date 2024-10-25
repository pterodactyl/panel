<?php

namespace LanguageServerProtocol;

class CodeActionClientCapabilitiesCodeActionLiteralSupport
{

    /**
     * The code action kind is supported with the following value
     * set.
     *
     * @var CodeActionClientCapabilitiesCodeActionLiteralSupportcodeActionKind
     */
    public $codeActionKind;

    public function __construct(CodeActionClientCapabilitiesCodeActionLiteralSupportcodeActionKind $codeActionKind)
    {
        $this->codeActionKind = $codeActionKind;
    }
}
