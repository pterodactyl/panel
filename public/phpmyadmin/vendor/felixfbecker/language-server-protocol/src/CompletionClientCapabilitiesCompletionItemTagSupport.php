<?php

namespace LanguageServerProtocol;

class CompletionClientCapabilitiesCompletionItemTagSupport
{

    /**
     * The tags supported by the client.
     *
     * @var int[]
     */
    public $valueSet;

    /**
     * @param int[]|null $valueSet CompletionItemTag
     */
    public function __construct(
        array $valueSet = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->valueSet = $valueSet;
    }
}
