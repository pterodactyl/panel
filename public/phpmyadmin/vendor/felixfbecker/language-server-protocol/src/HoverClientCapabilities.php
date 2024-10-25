<?php

namespace LanguageServerProtocol;

class HoverClientCapabilities
{

    /**
     * Whether hover supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * Client supports the follow content formats if the content
     * property refers to a `literal of type MarkupContent`.
     * The order describes the preferred format of the client.
     *
     * @var string[]|null
     * @see MarkupKind
     */
    public $contentFormat;

    /**
     * @param boolean|null $dynamicRegistration
     * @param string[]|null $contentFormat
     */
    public function __construct(
        bool $dynamicRegistration = null,
        array $contentFormat = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->contentFormat = $contentFormat;
    }
}
