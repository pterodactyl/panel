<?php

namespace LanguageServerProtocol;

class WorkspaceFolder
{
    /**
     * The associated URI for this workspace folder.
     *
     * @var string
     */
    public $uri;

    /**
     * The name of the workspace folder. Used to refer to this
     * workspace folder in the user interface.
     *
     * @var string
     */
    public $name;

    public function __construct(
        string $uri = null,
        string $name = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->uri = $uri;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->name = $name;
    }
}
