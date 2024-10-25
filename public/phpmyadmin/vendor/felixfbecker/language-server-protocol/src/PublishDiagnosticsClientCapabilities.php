<?php

namespace LanguageServerProtocol;

class PublishDiagnosticsClientCapabilities
{
/**
     * Whether the clients accepts diagnostics with related information.
     *
     * @var bool|null
     */
    public $relatedInformation;

    /**
     * Client supports the tag property to provide meta data about a diagnostic.
     * Clients supporting tags have to handle unknown tags gracefully.
     *
     * @since 3.15.0
     *
     * @var PublishDiagnosticsClientCapabilitiesTagSupport|null
     */
    public $tagSupport;

    /**
     * Whether the client interprets the version property of the
     * `textDocument/publishDiagnostics` notification's parameter.
     *
     * @since 3.15.0
     *
     * @var bool|null
     */
    public $versionSupport;

    /**
     * Client supports a codeDescription property
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $codeDescriptionSupport;

    /**
     * Whether code action supports the `data` property which is
     * preserved between a `textDocument/publishDiagnostics` and
     * `textDocument/codeAction` request.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $dataSupport;

    public function __construct(
        bool $relatedInformation = null,
        PublishDiagnosticsClientCapabilitiesTagSupport $tagSupport = null,
        bool $versionSupport = null,
        bool $codeDescriptionSupport = null,
        bool $dataSupport = null
    ) {
        $this->relatedInformation = $relatedInformation;
        $this->tagSupport = $tagSupport;
        $this->versionSupport = $versionSupport;
        $this->codeDescriptionSupport = $codeDescriptionSupport;
        $this->dataSupport = $dataSupport;
    }
}
