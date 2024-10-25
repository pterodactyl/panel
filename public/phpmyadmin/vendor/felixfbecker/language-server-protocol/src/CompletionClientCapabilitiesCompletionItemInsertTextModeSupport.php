<?php

namespace LanguageServerProtocol;

class CompletionClientCapabilitiesCompletionItemInsertTextModeSupport
{

    /**
     * The tags supported by the client.
     *
     * @var int[]
     */
    public $valueSet;

    /**
     * @param int[] $valueSet InsertTextMode
     */
    public function __construct(
        array $valueSet = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->valueSet = $valueSet;
    }
}
