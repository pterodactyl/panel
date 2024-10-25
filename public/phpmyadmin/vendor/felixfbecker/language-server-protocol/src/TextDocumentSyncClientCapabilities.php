<?php

namespace LanguageServerProtocol;

class TextDocumentSyncClientCapabilities
{

    /**
     * Whether text document synchronization supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports sending will save notifications.
     *
     * @var bool|null
     */
    public $willSave;

    /**
     * The client supports sending a will save request and
     * waits for a response providing text edits which will
     * be applied to the document before it is saved.
     *
     * @var bool|null
     */
    public $willSaveWaitUntil;

    /**
     * The client supports did save notifications.
     *
     * @var bool|null
     */
    public $didSave;

    public function __construct(
        bool $dynamicRegistration = null,
        bool $willSave = null,
        bool $willSaveWaitUntil = null,
        bool $didSave = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->willSave = $willSave;
        $this->willSaveWaitUntil = $willSaveWaitUntil;
        $this->didSave = $didSave;
    }
}
