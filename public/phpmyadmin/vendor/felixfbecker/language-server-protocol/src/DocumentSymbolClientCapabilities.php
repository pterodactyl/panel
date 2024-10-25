<?php

namespace LanguageServerProtocol;

class DocumentSymbolClientCapabilities
{

    /**
     * Whether document symbol supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * Specific capabilities for the `SymbolKind` in the
     * `textDocument/documentSymbol` request.
     *
     * @var DocumentSymbolClientCapabilitiesSymbolKind|null
     */
    public $symbolKind;

    /**
     * The client supports hierarchical document symbols.
     *
     * @var bool|null
     */
    public $hierarchicalDocumentSymbolSupport;

    /**
     * The client supports tags on `SymbolInformation`. Tags are supported on
     * `DocumentSymbol` if `hierarchicalDocumentSymbolSupport` is set to true.
     * Clients supporting tags have to handle unknown tags gracefully.
     *
     * @since 3.16.0
     *
     * @var DocumentSymbolClientCapabilitiesTagSupport|null
     */
    public $tagSupport;

    /**
     * The client supports an additional label presented in the UI when
     * registering a document symbol provider.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $labelSupport;

    public function __construct(
        bool $dynamicRegistration = null,
        DocumentSymbolClientCapabilitiesSymbolKind $symbolKind = null,
        bool $hierarchicalDocumentSymbolSupport = null,
        DocumentSymbolClientCapabilitiesTagSupport $tagSupport = null,
        bool $labelSupport = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->symbolKind = $symbolKind;
        $this->hierarchicalDocumentSymbolSupport = $hierarchicalDocumentSymbolSupport;
        $this->tagSupport = $tagSupport;
        $this->labelSupport = $labelSupport;
    }
}
