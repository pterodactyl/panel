<?php

namespace LanguageServerProtocol;

class ReferenceClientCapabilities
{
    /**
     * Whether references supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    public function __construct(bool $dynamicRegistration = null)
    {
        $this->dynamicRegistration = $dynamicRegistration;
    }
}
