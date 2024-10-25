<?php

namespace LanguageServerProtocol;

class TextDocumentIdentifier
{
    /**
     * The text document's URI.
     *
     * @var string
     */
    public $uri;

    /**
     * @param string $uri The text document's URI.
     */
    public function __construct(string $uri = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->uri = $uri;
    }
}
