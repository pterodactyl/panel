<?php

namespace LanguageServerProtocol;

/**
 * Value-object describing what options formatting should use.
 */
class FormattingOptions
{
    /**
     * Size of a tab in spaces.
     *
     * @var int
     */
    public $tabSize;

    /**
     * Prefer spaces over tabs.
     *
     * @var bool
     */
    public $insertSpaces;

    // Can be extended with further properties.

    public function __construct(int $tabSize = null, bool $insertSpaces = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->tabSize = $tabSize;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->insertSpaces = $insertSpaces;
    }
}
