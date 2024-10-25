<?php

namespace LanguageServerProtocol;

class ImplementationClientCapabilities
{
    /**
     * Whether implementation supports dynamic registration. If this is set to
     * `true` the client supports the new `ImplementationRegistrationOptions`
     * return value for the corresponding server capability as well.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports additional metadata in the form of definition links.
     *
     * @since 3.14.0
     *
     * @var boolean|null
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
