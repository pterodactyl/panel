<?php

namespace LanguageServerProtocol;

/**
 * Symbol tags are extra annotations that tweak the rendering of a symbol.
 *
 * @since 3.16
 */
abstract class SymbolTag
{
    /**
     * Render a symbol as obsolete, usually using a strike-out.
     */
    const DEPRECATED = 1;
}
