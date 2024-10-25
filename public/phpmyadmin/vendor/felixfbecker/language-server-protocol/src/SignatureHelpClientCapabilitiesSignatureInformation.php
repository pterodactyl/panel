<?php

namespace LanguageServerProtocol;

class SignatureHelpClientCapabilitiesSignatureInformation
{

    /**
     * Client supports the follow content formats for the documentation
     * property. The order describes the preferred format of the client.
     *
     * @var string[]|null
     * @see MarkupKind
     */
    public $documentationFormat;

    /**
     * Client capabilities specific to parameter information.
     *
     * @var SignatureHelpClientCapabilitiesSignatureInformationParameterInformation|null
     */
    public $parameterInformation;

    /**
     * The client supports the `activeParameter` property on
     * `SignatureInformation` literal.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $activeParameterSupport;

    /**
     * Undocumented function
     *
     * @param string[]|null $documentationFormat
     * @param SignatureHelpClientCapabilitiesSignatureInformationParameterInformation|null $parameterInformation
     * @param boolean|null $activeParameterSupport
     */
    public function __construct(
        array $documentationFormat = null,
        SignatureHelpClientCapabilitiesSignatureInformationParameterInformation $parameterInformation = null,
        bool $activeParameterSupport = null
    ) {
        $this->documentationFormat = $documentationFormat;
        $this->parameterInformation = $parameterInformation;
        $this->activeParameterSupport = $activeParameterSupport;
    }
}
