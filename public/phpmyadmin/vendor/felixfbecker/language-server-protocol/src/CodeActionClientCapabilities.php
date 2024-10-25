<?php

namespace LanguageServerProtocol;

class CodeActionClientCapabilities
{

    /**
     * Whether code action supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The client supports code action literals as a valid
     * response of the `textDocument/codeAction` request.
     *
     * @since 3.8.0
     *
     * @var CodeActionClientCapabilitiesCodeActionLiteralSupport|null
     */
    public $codeActionLiteralSupport;

    /**
     * Whether code action supports the `isPreferred` property.
     *
     * @since 3.15.0
     *
     * @var bool|null
     */
    public $isPreferredSupport;

    /**
     * Whether code action supports the `disabled` property.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $disabledSupport;

    /**
     * Whether code action supports the `data` property which is
     * preserved between a `textDocument/codeAction` and a
     * `codeAction/resolve` request.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $dataSupport;


    /**
     * Whether the client supports resolving additional code action
     * properties via a separate `codeAction/resolve` request.
     *
     * @since 3.16.0
     *
     * @var CodeActionClientCapabilitiesResolveSupport|null
     */
    public $resolveSupport;

    /**
     * Whether the client honors the change annotations in
     * text edits and resource operations returned via the
     * `CodeAction#edit` property by for example presenting
     * the workspace edit in the user interface and asking
     * for confirmation.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $honorsChangeAnnotations;

    public function __construct(
        bool $dynamicRegistration = null,
        CodeActionClientCapabilitiesCodeActionLiteralSupport $codeActionLiteralSupport = null,
        bool $isPreferredSupport = null,
        bool $disabledSupport = null,
        bool $dataSupport = null,
        CodeActionClientCapabilitiesResolveSupport $resolveSupport = null,
        bool $honorsChangeAnnotations = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->codeActionLiteralSupport = $codeActionLiteralSupport;
        $this->isPreferredSupport = $isPreferredSupport;
        $this->disabledSupport = $disabledSupport;
        $this->dataSupport = $dataSupport;
        $this->resolveSupport = $resolveSupport;
        $this->honorsChangeAnnotations = $honorsChangeAnnotations;
    }
}
