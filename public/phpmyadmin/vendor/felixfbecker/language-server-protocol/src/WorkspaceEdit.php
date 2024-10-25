<?php

namespace LanguageServerProtocol;

use JsonSerializable;

/**
 * A workspace edit represents changes to many resources managed in the workspace.
 */
class WorkspaceEdit implements JsonSerializable
{
    /**
     * Holds changes to existing resources. Associative Array from URI to TextEdit
     *
     * @var array<string, TextEdit[]>
     */
    public $changes;

    /**
     * Depending on the client capability
     * `workspace.workspaceEdit.resourceOperations` document changes are either
     * an array of `TextDocumentEdit`s to express changes to n different text
     * documents where each text document edit addresses a specific version of
     * a text document. Or it can contain above `TextDocumentEdit`s mixed with
     * create, rename and delete file / folder operations.
     *
     * Whether a client supports versioned document edits is expressed via
     * `workspace.workspaceEdit.documentChanges` client capability.
     *
     * If a client neither supports `documentChanges` nor
     * `workspace.workspaceEdit.resourceOperations` then only plain `TextEdit`s
     * using the `changes` property are supported.
     *
     * @var mixed
     */
    public $documentChanges;

    /**
     * A map of change annotations that can be referenced in
     * `AnnotatedTextEdit`s or create, rename and delete file / folder
     * operations.
     *
     * Whether clients honor this property depends on the client capability
     * `workspace.changeAnnotationSupport`.
     *
     * @since 3.16.0
     *
     * @var array<string, ChangeAnnotation>|null
     */
    public $changeAnnotations;

    /**
     * @param array<string, TextEdit[]> $changes
     * @param mixed $documentChanges
     * @param array<string, ChangeAnnotation>|null $changeAnnotations
     */
    public function __construct(
        array $changes = [],
        $documentChanges = null,
        array $changeAnnotations = null
    ) {
        $this->changes = $changes;
        $this->documentChanges = $documentChanges;
        $this->changeAnnotations = $changeAnnotations;
    }

    /**
     * This is needed because VSCode Does not like nulls
     * meaning if a null is sent then this will not compute
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}
