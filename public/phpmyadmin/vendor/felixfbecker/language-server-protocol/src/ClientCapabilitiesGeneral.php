<?php

namespace LanguageServerProtocol;

class ClientCapabilitiesGeneral
{
    /**
     * Client capabilities specific to regular expressions.
     *
     * @since 3.16.0
     *
     * @var RegularExpressionsClientCapabilities|null
     */
    public $regularExpressions;

    /**
     * Client capabilities specific to the client's markdown parser.
     *
     * @since 3.16.0
     *
     * @var MarkdownClientCapabilities|null
     */
    public $markdown;


    public function __construct(
        RegularExpressionsClientCapabilities $regularExpressions = null,
        MarkdownClientCapabilities $markdown = null
    ) {
        $this->regularExpressions = $regularExpressions;
        $this->markdown = $markdown;
    }
}
