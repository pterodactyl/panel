<?php

namespace LanguageServerProtocol;

class ClientCapabilities
{

    /**
     * Workspace specific client capabilities.
     *
     * @var ClientCapabilitiesWorkspace|null
     */
    public $workspace;

    /**
     * Text document specific client capabilities.
     *
     * @var TextDocumentClientCapabilities|null
     */
    public $textDocument;

    /**
     * Window specific client capabilities.
     *
     * @var ClientCapabilitiesWindow|null
     */
    public $window;

    /**
     * General client capabilities.
     *
     * @since 3.16.0
     *
     * @var ClientCapabilitiesGeneral|null
     */
    public $general;

    /**
     * Experimental client capabilities.
     *
     * @var mixed|null
     */
    public $experimental;

    /**
     * The client supports workspace/xfiles requests
     *
     * @var bool|null
     */
    public $xfilesProvider;

    /**
     * The client supports textDocument/xcontent requests
     *
     * @var bool|null
     */
    public $xcontentProvider;

    /**
     * The client supports xcache/* requests
     *
     * @var bool|null
     */
    public $xcacheProvider;

    /**
     * Undocumented function
     *
     * @param ClientCapabilitiesWorkspace|null $workspace
     * @param TextDocumentClientCapabilities|null $textDocument
     * @param ClientCapabilitiesWindow|null $window
     * @param ClientCapabilitiesGeneral|null $general
     * @param mixed|null $experimental
     * @param bool|null $xfilesProvider
     * @param bool|null $xcontentProvider
     * @param bool|null $xcacheProvider
     */
    public function __construct(
        ClientCapabilitiesWorkspace $workspace = null,
        TextDocumentClientCapabilities $textDocument = null,
        ClientCapabilitiesWindow $window = null,
        ClientCapabilitiesGeneral $general = null,
        $experimental = null,
        bool $xfilesProvider = null,
        bool $xcontentProvider = null,
        bool $xcacheProvider = null
    ) {
        $this->workspace = $workspace;
        $this->textDocument = $textDocument;
        $this->window = $window;
        $this->general = $general;
        $this->experimental = $experimental;
        $this->xfilesProvider = $xfilesProvider;
        $this->xcontentProvider = $xcontentProvider;
        $this->xcacheProvider = $xcacheProvider;
    }
}
