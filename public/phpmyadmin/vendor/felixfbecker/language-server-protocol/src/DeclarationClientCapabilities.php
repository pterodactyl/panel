<?php

namespace LanguageServerProtocol;

class DeclarationClientCapabilities
{

    /**
     * Whether declaration supports dynamic registration. If this is set to
     * `true` the client supports the new `DeclarationRegistrationOptions`
     * return value for the corresponding server capability as well.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports additional metadata in the form of declaration links.
     *
     * @var bool|null
     */
    public $linkSupport;

    public function __construct(
        bool $dynamicRegistration = null,
        bool $linkSupport = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->linkSupport = $linkSupport;
    }
}
