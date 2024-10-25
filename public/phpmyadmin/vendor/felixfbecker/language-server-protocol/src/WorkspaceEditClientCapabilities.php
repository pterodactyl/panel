<?php

namespace LanguageServerProtocol;

class WorkspaceEditClientCapabilities
{
    /**
     * The client supports versioned document changes in `WorkspaceEdit`s
     *
     * @var bool|null
     */
    public $documentChanges;

    /**
     * The resource operations the client supports. Clients should at least
     * support 'create', 'rename' and 'delete' files and folders.
     *
     * @since 3.13.0
     *
     * @var string[]|null
     * @see ResourceOperationKind
     */
    public $resourceOperations;

    /**
     * The failure handling strategy of a client if applying the workspace edit
     * fails.
     *
     * @since 3.13.0
     *
     * @var string|null
     * @see FailureHandlingKind
     */
    public $failureHandling;

    /**
     * Whether the client normalizes line endings to the client specific
     * setting.
     * If set to `true` the client will normalize line ending characters
     * in a workspace edit to the client specific new line character(s).
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $normalizesLineEndings;

    /**
     * Whether the client in general supports change annotations on text edits,
     * create file, rename file and delete file changes.
     *
     * @since 3.16.0
     *
     * @var WorkspaceEditClientCapabilitiesChangeAnnotationSupport|null
     */
    public $changeAnnotationSupport;

    /**
     * Undocumented function
     *
     * @param boolean|null $documentChanges
     * @param string[]|null $resourceOperations
     * @param string|null $failureHandling
     * @param boolean|null $normalizesLineEndings
     * @param WorkspaceEditClientCapabilitiesChangeAnnotationSupport|null $changeAnnotationSupport
     */
    public function __construct(
        bool $documentChanges = null,
        array $resourceOperations = null,
        string $failureHandling = null,
        bool $normalizesLineEndings = null,
        WorkspaceEditClientCapabilitiesChangeAnnotationSupport $changeAnnotationSupport = null
    ) {
        $this->documentChanges = $documentChanges;
        $this->resourceOperations = $resourceOperations;
        $this->failureHandling = $failureHandling;
        $this->normalizesLineEndings = $normalizesLineEndings;
        $this->changeAnnotationSupport = $changeAnnotationSupport;
    }
}
