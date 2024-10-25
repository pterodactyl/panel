<?php

namespace LanguageServerProtocol;

class CompletionClientCapabilities
{

    /**
     * Whether completion supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports the following `CompletionItem` specific
     * capabilities.
     *
     * @var CompletionClientCapabilitiesCompletionItem|null
     */
    public $completionItem;

    /**
     * The client supports to send additional context information for a
     * `textDocument/completion` request.
     *
     * @var bool|null
     */
    public $contextSupport;

    /**
     * The client's default when the completion item doesn't provide a
     * `insertTextMode` property.
     *
     * @since 3.17.0 - proposed state
     *
     * @var int|null
     * @see InsertTextFormat
     */
    public $insertTextMode;

    /**
     * The client supports the following `CompletionList` specific
     * capabilities.
     *
     * @since 3.17.0 - proposed state
     *
     * @var CompletionClientCapabilitiesCompletionList|null
     */
    public $completionList;

    public function __construct(
        bool $dynamicRegistration = null,
        CompletionClientCapabilitiesCompletionItem $completionItem = null,
        bool $contextSupport = null,
        int $insertTextMode = null,
        CompletionClientCapabilitiesCompletionList $completionList = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->completionItem = $completionItem;
        $this->contextSupport = $contextSupport;
        $this->insertTextMode = $insertTextMode;
        $this->completionList = $completionList;
    }
}
