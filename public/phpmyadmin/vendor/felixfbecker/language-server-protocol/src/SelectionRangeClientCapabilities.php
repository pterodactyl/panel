<?php

namespace LanguageServerProtocol;

class SelectionRangeClientCapabilities
{

    /**
     * Whether implementation supports dynamic registration for selection range
     * providers. If this is set to `true` the client supports the new
     * `SelectionRangeRegistrationOptions` return value for the corresponding
     * server capability as well.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    public function __construct(bool $dynamicRegistration = null)
    {
        $this->dynamicRegistration = $dynamicRegistration;
    }
}
