<?php

namespace LanguageServerProtocol;

/**
 * Client capabilities specific to parameter information.
 */
class SignatureHelpClientCapabilitiesSignatureInformationParameterInformation
{
    /**
     * The client supports processing label offsets instead of a
     * simple label string.
     *
     * @since 3.14.0
     *
     * @var bool|null
     */
    public $labelOffsetSupport;

    public function __construct(bool $labelOffsetSupport = null)
    {
        $this->labelOffsetSupport = $labelOffsetSupport;
    }
}
