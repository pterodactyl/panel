<?php

namespace LanguageServerProtocol;

class ReferenceContext
{
    /**
     * Include the declaration of the current symbol.
     *
     * @var bool
     */
    public $includeDeclaration;

    public function __construct(bool $includeDeclaration = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->includeDeclaration = $includeDeclaration;
    }
}
