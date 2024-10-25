<?php

namespace LanguageServerProtocol;

class DocumentLinkClientCapabilities
{

    /**
     * Whether text document synchronization supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * Whether the client supports the `tooltip` property on `DocumentLink`.
     *
     * @since 3.15.0
     *
     * @var bool|null
     */
    public $tooltipSupport;

    public function __construct(
        bool $dynamicRegistration = null,
        bool $tooltipSupport = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->tooltipSupport = $tooltipSupport;
    }
}
