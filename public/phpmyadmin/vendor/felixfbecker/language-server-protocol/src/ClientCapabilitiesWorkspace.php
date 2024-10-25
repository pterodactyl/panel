<?php

namespace LanguageServerProtocol;

class ClientCapabilitiesWorkspace
{
    /**
     * The client supports applying batch edits
     * to the workspace by supporting the request
     * 'workspace/applyEdit'
     *
     * @var bool|null
     */
    public $applyEdit;

    /**
     * Capabilities specific to `WorkspaceEdit`s
     *
     * @var WorkspaceEditClientCapabilities|null
     */
    public $workspaceEdit;

    /**
     * Capabilities specific to the `workspace/didChangeConfiguration`
     * notification.
     *
     * @var DidChangeConfigurationClientCapabilities|null
     */
    public $didChangeConfiguration;

    /**
     * Capabilities specific to the `workspace/didChangeWatchedFiles`
     * notification.
     *
     * @var DidChangeWatchedFilesClientCapabilities|null
     */
    public $didChangeWatchedFiles;

    /**
     * Capabilities specific to the `workspace/symbol` request.
     *
     * @var WorkspaceSymbolClientCapabilities|null
     */
    public $symbol;

    /**
     * Capabilities specific to the `workspace/executeCommand` request.
     *
     * @var ExecuteCommandClientCapabilities|null
     */
    public $executeCommand;

    /**
     * The client has support for workspace folders.
     *
     * @since 3.6.0
     *
     * @var bool|null
     */
    public $workspaceFolders;

    /**
     * The client supports `workspace/configuration` requests.
     *
     * @since 3.6.0
     *
     * @var bool|null
     */
    public $configuration;

    /**
     * Capabilities specific to the semantic token requests scoped to the
     * workspace.
     *
     * @since 3.16.0
     *
     * @var SemanticTokensWorkspaceClientCapabilities|null
     */
    public $semanticTokens;

    /**
     * Capabilities specific to the code lens requests scoped to the
     * workspace.
     *
     * @since 3.16.0
     *
     * @var CodeLensWorkspaceClientCapabilities|null
     */
    public $codeLens;

    /**
     * The client has support for file requests/notifications.
     *
     * @since 3.16.0
     *
     * @var ClientCapabilitiesWorkspaceFileOperations|null
     */
    public $fileOperations;
}
