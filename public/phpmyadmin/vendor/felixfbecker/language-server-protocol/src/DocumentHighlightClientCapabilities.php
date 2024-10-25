<?php

namespace LanguageServerProtocol;

class DocumentHighlightClientCapabilities
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
