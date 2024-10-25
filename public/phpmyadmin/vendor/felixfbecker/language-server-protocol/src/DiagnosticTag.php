<?php

namespace LanguageServerProtocol;

/**
 * How whitespace and indentation is handled during completion
 * item insertion.
 *
 * @since 3.16.0
 */
abstract class DiagnosticTag
{
    /**
     * Unused or unnecessary code.
     *
     * Clients are allowed to render diagnostics with this tag faded out
     * instead of having an error squiggle.
     */
    const UNNECESSARY = 1;

    /**
     * Deprecated or obsolete code.
     *
     * Clients are allowed to rendered diagnostics with this tag strike through.
     */
    const DEPRECATED = 2;
}
