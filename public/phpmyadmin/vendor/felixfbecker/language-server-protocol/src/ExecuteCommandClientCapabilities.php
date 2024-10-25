<?php

namespace LanguageServerProtocol;

class ExecuteCommandClientCapabilities
{

    /**
     * Execute command supports dynamic registration.
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
