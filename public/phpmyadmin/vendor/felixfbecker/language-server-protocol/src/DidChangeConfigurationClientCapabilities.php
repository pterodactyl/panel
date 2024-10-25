<?php

namespace LanguageServerProtocol;

class DidChangeConfigurationClientCapabilities
{

    /**
     * Did change configuration notification supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    public function __construct(
        bool $dynamicRegistration = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
    }
}
