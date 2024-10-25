<?php

namespace LanguageServerProtocol;

class CompletionClientCapabilitiesCompletionItem
{

    /**
     * Client supports snippets as insert text.
     *
     * A snippet can define tab stops and placeholders with `$1`, `$2`
     * and `${3:foo}`. `$0` defines the final tab stop, it defaults to
     * the end of the snippet. Placeholders with equal identifiers are
     * linked, that is typing in one will update others too.
     *
     * @var bool|null
     */
    public $snippetSupport;

    /**
     * Client supports commit characters on a completion item.
     *
     * @var bool|null
     */
    public $commitCharactersSupport;

    /**
     * Client supports the follow content formats for the documentation
     * property. The order describes the preferred format of the client.
     *
     * @var string[]|null
     */
    public $documentationFormat;

    /**
     * Client supports the deprecated property on a completion item.
     *
     * @var bool|null
     */
    public $deprecatedSupport;

    /**
     * Client supports the preselect property on a completion item.
     *
     * @var bool|null
     */
    public $preselectSupport;

    /**
     * Client supports the tag property on a completion item. Clients
     * supporting tags have to handle unknown tags gracefully. Clients
     * especially need to preserve unknown tags when sending a completion
     * item back to the server in a resolve call.
     *
     * @since 3.15.0
     *
     * @var CompletionClientCapabilitiesCompletionItemTagSupport|null
     */
    public $tagSupport;

    /**
     * Client supports insert replace edit to control different behavior if
     * a completion item is inserted in the text or should replace text.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $insertReplaceSupport;

    /**
     * Indicates which properties a client can resolve lazily on a
     * completion item. Before version 3.16.0 only the predefined properties
     * `documentation` and `detail` could be resolved lazily.
     *
     * @since 3.16.0
     *
     * @var CompletionClientCapabilitiesCompletionItemResolveSupport|null
     */
    public $resolveSupport;

    /**
     * The client supports the `insertTextMode` property on
     * a completion item to override the whitespace handling mode
     * as defined by the client (see `insertTextMode`).
     *
     * @since 3.16.0
     *
     * @var CompletionClientCapabilitiesCompletionItemInsertTextModeSupport|null
     */
    public $insertTextModeSupport;

    /**
     * The client has support for completion item label
     * details (see also `CompletionItemLabelDetails`).
     *
     * @since 3.17.0 - proposed state
     *
     * @var bool|null
     */
    public $labelDetailsSupport;

    /**
     * Undocumented function
     *
     * @param boolean|null $snippetSupport
     * @param boolean|null $commitCharactersSupport
     * @param string[]|null $documentationFormat
     * @param boolean|null $deprecatedSupport
     * @param boolean|null $preselectSupport
     * @param CompletionClientCapabilitiesCompletionItemTagSupport|null $tagSupport
     * @param boolean|null $insertReplaceSupport
     * @param CompletionClientCapabilitiesCompletionItemResolveSupport|null $resolveSupport
     * @param CompletionClientCapabilitiesCompletionItemInsertTextModeSupport|null $insertTextModeSupport
     * @param boolean|null $labelDetailsSupport
     */
    public function __construct(
        bool $snippetSupport = null,
        bool $commitCharactersSupport = null,
        array $documentationFormat = null,
        bool $deprecatedSupport = null,
        bool $preselectSupport = null,
        CompletionClientCapabilitiesCompletionItemTagSupport $tagSupport = null,
        bool $insertReplaceSupport = null,
        CompletionClientCapabilitiesCompletionItemResolveSupport $resolveSupport = null,
        CompletionClientCapabilitiesCompletionItemInsertTextModeSupport $insertTextModeSupport = null,
        bool $labelDetailsSupport = null
    ) {
        $this->snippetSupport = $snippetSupport;
        $this->commitCharactersSupport = $commitCharactersSupport;
        $this->documentationFormat = $documentationFormat;
        $this->deprecatedSupport = $deprecatedSupport;
        $this->preselectSupport = $preselectSupport;
        $this->tagSupport = $tagSupport;
        $this->insertReplaceSupport = $insertReplaceSupport;
        $this->resolveSupport = $resolveSupport;
        $this->insertTextModeSupport = $insertTextModeSupport;
        $this->labelDetailsSupport = $labelDetailsSupport;
    }
}
