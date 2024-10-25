<?php

namespace LanguageServerProtocol;

class ChangeAnnotation
{

/**
     * A human-readable string describing the actual change. The string
     * is rendered prominent in the user interface.
     *
     * @var string
     */
    public $label;

    /**
     * A flag which indicates that user confirmation is needed
     * before applying the change.
     *
     * @var bool|null
     */
    public $needsConfirmation;

    /**
     * A human-readable string which is rendered less prominent in
     * the user interface.
     *
     * @var string|null
     */
    public $description;

    public function __construct(string $label = null, bool $needsConfirmation = null, string $description = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->label = $label;
        $this->needsConfirmation = $needsConfirmation;
        $this->description = $description;
    }
}
