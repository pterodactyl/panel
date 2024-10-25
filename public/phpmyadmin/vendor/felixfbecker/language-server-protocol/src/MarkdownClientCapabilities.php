<?php

namespace LanguageServerProtocol;

class MarkdownClientCapabilities
{

    /**
     * The name of the parser.
     *
     * @var string
     */
    public $parser;

    /**
     * The version of the parser.
     *
     * @var string|null
     */
    public $version;

    /**
     * A list of HTML tags that the client allows / supports in
     * Markdown.
     *
     * @since 3.17.0
     *
     * @var string[]|null
     */
    public $allowedTags;

    /**
     * @param string|null $parser
     * @param string|null $version
     * @param string[]|null $allowedTags
     */
    public function __construct(
        string $parser = null,
        string $version = null,
        array $allowedTags = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->parser = $parser;
        $this->version = $version;
        $this->allowedTags = $allowedTags;
    }
}
