<?php

namespace LanguageServerProtocol;

class ShowMessageRequestClientCapabilitiesMessageActionItem
{

    /**
     * Whether the client supports additional attributes which
     * are preserved and sent back to the server in the
     * request's response.
     *
     * @var bool|null
     */
    public $additionalPropertiesSupport;


    public function __construct(bool $additionalPropertiesSupport = null)
    {
        $this->additionalPropertiesSupport = $additionalPropertiesSupport;
    }
}
