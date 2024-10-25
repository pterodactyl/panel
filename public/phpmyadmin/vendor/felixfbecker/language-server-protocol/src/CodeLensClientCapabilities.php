<?php

namespace LanguageServerProtocol;

class CodeLensClientCapabilities
{

    /**
     * Whether text document synchronization supports dynamic registration.
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
