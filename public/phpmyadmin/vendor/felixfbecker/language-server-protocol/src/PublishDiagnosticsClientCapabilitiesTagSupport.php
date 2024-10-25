<?php

namespace LanguageServerProtocol;

class PublishDiagnosticsClientCapabilitiesTagSupport
{
    /**
     * The tags supported by the client.
     *
     * @var int[]
     * @see DiagnosticTag
     */
    public $valueSet;

    /**
     * @param int[]|null $valueSet
     */
    public function __construct(array $valueSet = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->valueSet = $valueSet;
    }
}
