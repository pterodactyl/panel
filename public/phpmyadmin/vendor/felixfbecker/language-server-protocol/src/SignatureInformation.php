<?php

namespace LanguageServerProtocol;

/**
 * Represents the signature of something callable. A signature
 * can have a label, like a function-name, a doc-comment, and
 * a set of parameters.
 */
class SignatureInformation
{
    /**
     * The label of this signature. Will be shown in
     * the UI.
     *
     * @var string
     */
    public $label;

    /**
     * The human-readable doc-comment of this signature. Will be shown
     * in the UI but can be omitted.
     *
     * @var MarkupContent|string|null
     */
    public $documentation;

    /**
     * The parameters of this signature.
     *
     * @var ParameterInformation[]|null
     */
    public $parameters;

    /**
     * The index of the active parameter.
     *
     * If provided, this is used in place of `SignatureHelp.activeParameter`.
     *
     * @since 3.16.0
     *
     * @var int|null
     */
    public $activeParameter;

    /**
     * Create a SignatureInformation
     *
     * @param string $label                           The label of this signature. Will be shown in the UI.
     * @param ParameterInformation[]|null $parameters The parameters of this signature
     * @param MarkupContent|string|null $documentation  The human-readable doc-comment of this signature.
     *                                                  Will be shown in the UI but can be omitted.
     * @param int|null $activeParameter The index of the active parameter.
     */
    public function __construct(
        string $label,
        array $parameters = null,
        $documentation = null,
        int $activeParameter = null
    ) {
        $this->label = $label;
        $this->parameters = $parameters;
        $this->documentation = $documentation;
        $this->activeParameter = $activeParameter;
    }
}
