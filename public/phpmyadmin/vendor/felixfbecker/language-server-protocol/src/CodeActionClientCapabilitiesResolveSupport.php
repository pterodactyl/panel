<?php

namespace LanguageServerProtocol;

class CodeActionClientCapabilitiesResolveSupport
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
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }
}
