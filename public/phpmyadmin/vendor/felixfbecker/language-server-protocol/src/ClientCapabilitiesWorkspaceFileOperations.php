<?php

namespace LanguageServerProtocol;

class ClientCapabilitiesWorkspaceFileOperations
{

    /**
     * Whether the client supports dynamic registration for file
     * requests/notifications.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client has support for sending didCreateFiles notifications.
     *
     * @var bool|null
     */
    public $didCreate;

    /**
     * The client has support for sending willCreateFiles requests.
     *
     * @var bool|null
     */
    public $willCreate;

    /**
     * The client has support for sending didRenameFiles notifications.
     *
     * @var bool|null
     */
    public $didRename;

    /**
     * The client has support for sending willRenameFiles requests.
     *
     * @var bool|null
     */
    public $willRename;

    /**
     * The client has support for sending didDeleteFiles notifications.
     *
     * @var bool|null
     */
    public $didDelete;

    /**
     * The client has support for sending willDeleteFiles requests.
     *
     * @var bool|null
     */
    public $willDelete;

    public function __construct(
        bool $dynamicRegistration = null,
        bool $didCreate = null,
        bool $willCreate = null,
        bool $didRename = null,
        bool $willRename = null,
        bool $didDelete = null,
        bool $willDelete = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->didCreate = $didCreate;
        $this->willCreate = $willCreate;
        $this->didRename = $didRename;
        $this->willRename = $willRename;
        $this->didDelete = $didDelete;
        $this->willDelete = $willDelete;
    }
}
