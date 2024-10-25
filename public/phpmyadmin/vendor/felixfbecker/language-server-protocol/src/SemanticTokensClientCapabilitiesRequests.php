<?php

namespace LanguageServerProtocol;

class SemanticTokensClientCapabilitiesRequests
{

    /**
     * The client will send the `textDocument/semanticTokens/range` request
     * if the server provides a corresponding handler.
     *
     * @var mixed|null
     */
    public $range;

    /**
     * The client will send the `textDocument/semanticTokens/full` request
     * if the server provides a corresponding handler.
     *
     * @var mixed|null
     */
    public $full;

    public function __construct(
        bool $range = null,
        bool $full = null
    ) {
        $this->range = $range;
        $this->full = $full;
    }
}
