<?php

namespace LanguageServerProtocol;

class TextDocumentClientCapabilities
{

    /**
     * @var TextDocumentSyncClientCapabilities|null
     */
    public $synchronization;

    /**
     * Capabilities specific to the `textDocument/completion` request.
     *
     * @var CompletionClientCapabilities|null
     */
    public $completion;

    /**
     * Capabilities specific to the `textDocument/hover` request.
     *
     * @var HoverClientCapabilities|null
     */
    public $hover;

    /**
     * Capabilities specific to the `textDocument/signatureHelp` request.
     *
     * @var SignatureHelpClientCapabilities|null
     */
    public $signatureHelp;

    /**
     * Capabilities specific to the `textDocument/declaration` request.
     *
     * @since 3.14.0
     *
     * @var DeclarationClientCapabilities|null
     */
    public $declaration;

    /**
     * Capabilities specific to the `textDocument/definition` request.
     *
     * @var DefinitionClientCapabilities|null
     */
    public $definition;

    /**
     * Capabilities specific to the `textDocument/typeDefinition` request.
     *
     * @since 3.6.0
     *
     * @var TypeDefinitionClientCapabilities|null
     */
    public $typeDefinition;

    /**
     * Capabilities specific to the `textDocument/implementation` request.
     *
     * @since 3.6.0
     *
     * @var ImplementationClientCapabilities|null
     */
    public $implementation;

    /**
     * Capabilities specific to the `textDocument/references` request.
     *
     * @var ReferenceClientCapabilities|null
     */
    public $references;

    /**
     * Capabilities specific to the `textDocument/documentHighlight` request.
     *
     * @var DocumentHighlightClientCapabilities|null
     */
    public $documentHighlight;

    /**
     * Capabilities specific to the `textDocument/documentSymbol` request.
     *
     * @var DocumentSymbolClientCapabilities|null
     */
    public $documentSymbol;

    /**
     * Capabilities specific to the `textDocument/codeAction` request.
     *
     * @var CodeActionClientCapabilities|null
     */
    public $codeAction;

    /**
     * Capabilities specific to the `textDocument/codeLens` request.
     *
     * @var CodeLensClientCapabilities|null
     */
    public $codeLens;

    /**
     * Capabilities specific to the `textDocument/documentLink` request.
     *
     * @var DocumentLinkClientCapabilities|null
     */
    public $documentLink;

    /**
     * Capabilities specific to the `textDocument/documentColor` and the
     * `textDocument/colorPresentation` request.
     *
     * @since 3.6.0
     *
     * @var DocumentColorClientCapabilities|null
     */
    public $colorProvider;

    /**
     * Capabilities specific to the `textDocument/formatting` request.
     *
     * @var DocumentFormattingClientCapabilities|null
     */
    public $formatting;

    /**
     * Capabilities specific to the `textDocument/rangeFormatting` request.
     *
     * @var DocumentRangeFormattingClientCapabilities|null
     */
    public $rangeFormatting;

    /** request.
     * Capabilities specific to the `textDocument/onTypeFormatting` request.
     *
     * @var DocumentOnTypeFormattingClientCapabilities|null
     */
    public $onTypeFormatting;

    /**
     * Capabilities specific to the `textDocument/rename` request.
     *
     * @var RenameClientCapabilities|null
     */
    public $rename;

    /**
     * Capabilities specific to the `textDocument/publishDiagnostics`
     * notification.
     *
     * @var PublishDiagnosticsClientCapabilities|null
     */
    public $publishDiagnostics;

    /**
     * Capabilities specific to the `textDocument/foldingRange` request.
     *
     * @since 3.10.0
     *
     * @var FoldingRangeClientCapabilities|null
     */
    public $foldingRange;

    /**
     * Capabilities specific to the `textDocument/selectionRange` request.
     *
     * @since 3.15.0
     *
     * @var SelectionRangeClientCapabilities|null
     */
    public $selectionRange;

    /**
     * Capabilities specific to the `textDocument/linkedEditingRange` request.
     *
     * @since 3.16.0
     *
     * @var LinkedEditingRangeClientCapabilities|null
     */
    public $linkedEditingRange;

    /**
     * Capabilities specific to the various call hierarchy requests.
     *
     * @since 3.16.0
     *
     * @var CallHierarchyClientCapabilities|null
     */
    public $callHierarchy;

    /**
     * Capabilities specific to the various semantic token requests.
     *
     * @since 3.16.0
     *
     * @var SemanticTokensClientCapabilities|null
     */
    public $semanticTokens;

    /**
     * Capabilities specific to the `textDocument/moniker` request.
     *
     * @since 3.16.0
     *
     * @var MonikerClientCapabilities|null
     */
    public $moniker;

    public function __construct(
        TextDocumentSyncClientCapabilities $synchronization = null,
        CompletionClientCapabilities $completion = null,
        HoverClientCapabilities $hover = null,
        SignatureHelpClientCapabilities $signatureHelp = null,
        DeclarationClientCapabilities $declaration = null,
        DefinitionClientCapabilities $definition = null,
        TypeDefinitionClientCapabilities $typeDefinition = null,
        ImplementationClientCapabilities $implementation = null,
        ReferenceClientCapabilities $references = null,
        DocumentHighlightClientCapabilities $documentHighlight = null,
        DocumentSymbolClientCapabilities $documentSymbol = null,
        CodeActionClientCapabilities $codeAction = null,
        CodeLensClientCapabilities $codeLens = null,
        DocumentLinkClientCapabilities $documentLink = null,
        DocumentColorClientCapabilities $colorProvider = null,
        DocumentFormattingClientCapabilities $formatting = null,
        DocumentRangeFormattingClientCapabilities $rangeFormatting = null,
        DocumentOnTypeFormattingClientCapabilities $onTypeFormatting = null,
        RenameClientCapabilities $rename = null,
        PublishDiagnosticsClientCapabilities $publishDiagnostics = null,
        FoldingRangeClientCapabilities $foldingRange = null,
        SelectionRangeClientCapabilities $selectionRange = null,
        LinkedEditingRangeClientCapabilities $linkedEditingRange = null,
        CallHierarchyClientCapabilities $callHierarchy = null,
        SemanticTokensClientCapabilities $semanticTokens = null,
        MonikerClientCapabilities $moniker = null
    ) {
        $this->synchronization = $synchronization;
        $this->completion = $completion;
        $this->hover = $hover;
        $this->signatureHelp = $signatureHelp;
        $this->declaration = $declaration;
        $this->definition = $definition;
        $this->typeDefinition = $typeDefinition;
        $this->implementation = $implementation;
        $this->references = $references;
        $this->documentHighlight = $documentHighlight;
        $this->documentSymbol = $documentSymbol;
        $this->codeAction = $codeAction;
        $this->codeLens = $codeLens;
        $this->documentLink = $documentLink;
        $this->colorProvider = $colorProvider;
        $this->formatting = $formatting;
        $this->rangeFormatting = $rangeFormatting;
        $this->onTypeFormatting = $onTypeFormatting;
        $this->rename = $rename;
        $this->publishDiagnostics = $publishDiagnostics;
        $this->foldingRange = $foldingRange;
        $this->selectionRange = $selectionRange;
        $this->linkedEditingRange = $linkedEditingRange;
        $this->callHierarchy = $callHierarchy;
        $this->semanticTokens = $semanticTokens;
        $this->moniker = $moniker;
    }
}
