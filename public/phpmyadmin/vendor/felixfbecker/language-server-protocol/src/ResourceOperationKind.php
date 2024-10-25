<?php

namespace LanguageServerProtocol;

/**
 * Defines how the host (editor) should sync document changes to the language server.
 */
abstract class ResourceOperationKind
{
    /**
     * Supports creating new files and folders.
     */
    const CREATE = 'create';

    /**
     * Supports renaming existing files and folders.
     */
    const RENAME = 'rename';

    /**
     * Supports deleting existing files and folders.
     */
    const DELETE  = 'delete';
}
