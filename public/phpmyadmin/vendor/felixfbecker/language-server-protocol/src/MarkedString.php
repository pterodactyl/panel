<?php

namespace LanguageServerProtocol;

class MarkedString
{
    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $value;

    public function __construct(string $language = null, string $value = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->language = $language;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->value = $value;
    }
}
