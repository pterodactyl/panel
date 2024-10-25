<?php

namespace LanguageServerProtocol;

class CompletionClientCapabilitiesCompletionItemResolveSupport
{

    /**
     * The properties that a client can resolve lazily.
     *
     * @var string[]
     */
    public $properties;

    /**
     * @param string[] $properties
     */
    public function __construct(
        array $properties = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->properties = $properties;
    }
}
