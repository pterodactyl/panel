<?php

namespace LanguageServerProtocol;

class ShowDocumentClientCapabilities
{
    /**
     * The client has support for the show document
     * request.
     *
     * @var bool|null
     */
    public $support;

    public function __construct(?bool $support = null)
    {
        $this->support = $support;
    }
}
