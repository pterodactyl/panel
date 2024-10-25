<?php

namespace LanguageServerProtocol;

/**
 * A set of predefined code action kinds.
 */
abstract class CodeActionKind
{
/**
     * Empty kind.
     */
    const EMPTY = '';

    /**
     * Base kind for quickfix actions: 'quickfix'.
     */
    const QUICK_FIX = 'quickfix';

    /**
     * Base kind for refactoring actions: 'refactor'.
     */
    const REFACTOR = 'refactor';

    /**
     * Base kind for refactoring extraction actions: 'refactor.extract'.
     *
     * Example extract actions:
     *
     * - Extract method
     * - Extract function
     * - Extract variable
     * - Extract interface from class
     * - ...
     */
    const REFACTOR_EXTRACT = 'refactor.extract';

    /**
     * Base kind for refactoring inline actions: 'refactor.inline'.
     *
     * Example inline actions:
     *
     * - Inline function
     * - Inline variable
     * - Inline constant
     * - ...
     */
    const REFACTOR_INLINE = 'refactor.inline';

    /**
     * Base kind for refactoring rewrite actions: 'refactor.rewrite'.
     *
     * Example rewrite actions:
     *
     * - Convert JavaScript function to class
     * - Add or remove parameter
     * - Encapsulate field
     * - Make method static
     * - Move method to base class
     * - ...
     */
    const REFACTOR_REWRITE = 'refactor.rewrite';

    /**
     * Base kind for source actions: `source`.
     *
     * Source code actions apply to the entire file.
     */
    const SOURCE = 'source';

    /**
     * Base kind for an organize imports source action:
     * `source.organizeImports`.
     */
    const SOURCE_ORGANIZE_IMPORTS = 'source.organizeImports';

    /**
     * Base kind for a 'fix all' source action: `source.fixAll`.
     *
     * 'Fix all' actions automatically fix errors that have a clear fix that
     * do not require user input. They should not suppress errors or perform
     * unsafe fixes such as generating new types or classes.
     *
     * @since 3.17.0
     */
    const SOURCE_FIX_ALL = 'source.fixAll';
}
