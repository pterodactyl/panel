<?php

namespace LanguageServerProtocol;

class CallHierarchyClientCapabilities
{

    /**
     * Whether implementation supports dynamic registration. If this is set to
     * `true` the client supports the new `(TextDocumentRegistrationOptions &
     * StaticRegistrationOptions)` return value for the corresponding server
     * capability as well.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    public function __construct(?bool $dynamicRegistration = null)
    {
        $this->dynamicRegistration = $dynamicRegistration;
    }
}
