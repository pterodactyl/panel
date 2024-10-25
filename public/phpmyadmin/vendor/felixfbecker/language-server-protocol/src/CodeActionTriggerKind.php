<?php

namespace LanguageServerProtocol;

/**
 * A set of predefined code action kinds.
 */
abstract class CodeActionTriggerKind
{
    /**
     * Code actions were explicitly requested by the user or by an extension.
     */
    const INVOKED = 1;

    /**
     * Code actions were requested automatically.
     *
     * This typically happens when current selection in a file changes, but can
     * also be triggered when file content changes.
     */
    const AUTOMATIC = 2;
}
