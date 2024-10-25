<?php

namespace LanguageServerProtocol;

class DefinitionClientCapabilities
{

    /**
     * Whether definition supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports additional metadata in the form of definition links.
     *
     * @since 3.14.0
     *
     * @var bool|null
     */
    public $linkSupport;

    public function __construct(bool $dynamicRegistration = null, bool $linkSupport = null)
    {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->linkSupport = $linkSupport;
    }
}
