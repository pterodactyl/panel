<?php

namespace LanguageServerProtocol;

class RegularExpressionsClientCapabilities
{

    /**
     * The engine's name.
     *
     * @var string
     */
    public $engine;

    /**
     * The engine's version.
     *
     * @var string|null
     */
    public $version;


    /**
     * @param string $engine
     * @param string|null $version
     */
    public function __construct(string $engine = null, string $version = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->engine = $engine;
        $this->version = $version;
    }
}
