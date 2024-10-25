<?php

namespace LanguageServerProtocol;

class WorkspaceSymbolClientCapabilitiesResolveSupport
{
    /**
     * The properties that a client can resolve lazily. Usually
     * `location.range`
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
