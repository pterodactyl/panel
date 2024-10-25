<?php

namespace LanguageServerProtocol;

class SignatureHelpClientCapabilities
{
    /**
     * Whether signature help supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports the following `SignatureInformation`
     * specific properties.
     *
     * @var SignatureHelpClientCapabilitiesSignatureInformation|null
     */
    public $signatureInformation;

    /**
     * The client supports to send additional context information for a
     * `textDocument/signatureHelp` request. A client that opts into
     * contextSupport will also support the `retriggerCharacters` on
     * `SignatureHelpOptions`.
     *
     * @since 3.15.0
     *
     * @var bool|null
     */
    public $contextSupport;

    public function __construct(
        bool $dynamicRegistration = null,
        SignatureHelpClientCapabilitiesSignatureInformation $signatureInformation = null,
        bool $contextSupport = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->signatureInformation = $signatureInformation;
        $this->contextSupport = $contextSupport;
    }
}
