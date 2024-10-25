<?php

namespace LanguageServerProtocol;

class MessageActionItem
{
    /**
     * A short title like 'Retry', 'Open Log' etc.
     *
     * @var string
     */
    public $title;

    public function __construct(string $title = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->title = $title;
    }
}
