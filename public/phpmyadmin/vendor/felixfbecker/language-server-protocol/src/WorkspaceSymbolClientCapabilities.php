<?php

namespace LanguageServerProtocol;

class WorkspaceSymbolClientCapabilities
{

    /**
     * Symbol request supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * Specific capabilities for the `SymbolKind` in the `workspace/symbol`
     * request.
     *
     * @var WorkspaceSymbolClientCapabilitiesSymbolKind|null
     */
    public $symbolKind;

    /**
     * The client supports tags on `SymbolInformation` and `WorkspaceSymbol`.
     * Clients supporting tags have to handle unknown tags gracefully.
     *
     * @since 3.16.0
     *
     * @var WorkspaceSymbolClientCapabilitiesTagSupport|null
     */
    public $tagSupport;

    /**
     * The client support partial workspace symbols. The client will send the
     * request `workspaceSymbol/resolve` to the server to resolve additional
     * properties.
     *
     * @since 3.17.0 - proposedState
     *
     * @var WorkspaceSymbolClientCapabilitiesResolveSupport|null
     */
    public $resolveSupport;

    public function __construct(
        bool $dynamicRegistration = null,
        WorkspaceSymbolClientCapabilitiesSymbolKind $symbolKind = null,
        WorkspaceSymbolClientCapabilitiesTagSupport $tagSupport = null,
        WorkspaceSymbolClientCapabilitiesResolveSupport $resolveSupport = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->symbolKind = $symbolKind;
        $this->tagSupport = $tagSupport;
        $this->resolveSupport = $resolveSupport;
    }
}
