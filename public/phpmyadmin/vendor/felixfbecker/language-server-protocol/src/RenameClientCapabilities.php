<?php

namespace LanguageServerProtocol;

class RenameClientCapabilities
{

    /**
     * Whether text document synchronization supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * Client supports testing for validity of rename operations
     * before execution.
     *
     * @since version 3.12.0
     *
     * @var bool|null
     */
    public $prepareSupport;

    /**
     * Client supports the default behavior result
     * (`{ defaultBehavior: boolean }`).
     *
     * The value indicates the default behavior used by the
     * client.
     *
     * @since version 3.16.0
     *
     * @var int|null
     */
    public $prepareSupportDefaultBehavior;

    /**
     * Whether th client honors the change annotations in
     * text edits and resource operations returned via the
     * rename request's workspace edit by for example presenting
     * the workspace edit in the user interface and asking
     * for confirmation.
     *
     * @since 3.16.0
     *
     * @var bool|null
     */
    public $honorsChangeAnnotations;

    /**
     * @param boolean|null $dynamicRegistration
     * @param boolean|null $prepareSupport
     * @param integer|null $prepareSupportDefaultBehavior PrepareSupportDefaultBehavior
     * @param boolean|null $honorsChangeAnnotations
     */
    public function __construct(
        bool $dynamicRegistration = null,
        bool $prepareSupport = null,
        int $prepareSupportDefaultBehavior = null,
        bool $honorsChangeAnnotations = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->prepareSupport = $prepareSupport;
        $this->prepareSupportDefaultBehavior = $prepareSupportDefaultBehavior;
        $this->honorsChangeAnnotations = $honorsChangeAnnotations;
    }
}
