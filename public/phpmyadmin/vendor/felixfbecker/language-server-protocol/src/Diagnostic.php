<?php

namespace LanguageServerProtocol;

/**
 * Represents a diagnostic, such as a compiler error or warning. Diagnostic objects are only valid in the scope of a
 * resource.
 */
class Diagnostic
{
    /**
     * The range at which the message applies.
     *
     * @var Range
     */
    public $range;

    /**
     * The diagnostic's severity. Can be omitted. If omitted it is up to the
     * client to interpret diagnostics as error, warning, info or hint.
     *
     * @var int|null
     */
    public $severity;

    /**
     * The diagnostic's code. which might appear in the user interface
     *
     * @var int|null
     */
    public $code;

    /**
     * An optional property to describe the error code.
     *
     * @since 3.16.0
     *
     * @var CodeDescription|null
     */
    public $codeDescription;

    /**
     * A human-readable string describing the source of this
     * diagnostic, e.g. 'typescript' or 'super lint'.
     *
     * @var string|null
     */
    public $source;

    /**
     * The diagnostic's message.
     *
     * @var string
     */
    public $message;

    /**
     * Additional metadata about the diagnostic.
     *
     * @since 3.15.0
     *
     * @var int[]|null
     * @see DiagnosticTag
     */
    public $tags;

    /**
     * An array of related diagnostic information, e.g. when symbol-names within
     * a scope collide all definitions can be marked via this property.
     *
     * @var DiagnosticRelatedInformation[]|null
     */
    public $relatedInformation;

    /**
     * A data entry field that is preserved between a
     * `textDocument/publishDiagnostics` notification and
     * `textDocument/codeAction` request.
     *
     * @since 3.16.0
     *
     * @var mixed|null
     */
    public $data;

    /**
     * @param  string|null $message  The diagnostic's message
     * @param  Range|null  $range    The range at which the message applies
     * @param  int|null    $code     The diagnostic's code
     * @param  int|null    $severity DiagnosticSeverity
     * @param  string|null $source   A human-readable string describing the source of this diagnostic
     * @param  CodeDescription|null $codeDescription
     * @param  int[]|null  $tags     Additional metadata about the diagnostic
     * @param  DiagnosticRelatedInformation[]|null  $relatedInformation Related diagnostic information
     * @param  mixed  $data     A data entry field that is preserved between a `textDocument/publishDiagnostics`
     *                          notification and `textDocument/codeAction` request
     */
    public function __construct(
        string $message = null,
        Range $range = null,
        int $code = null,
        int $severity = null,
        string $source = null,
        CodeDescription $codeDescription = null,
        array $tags = null,
        array $relatedInformation = null,
        $data = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->message = $message;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->range = $range;
        $this->code = $code;
        $this->severity = $severity;
        $this->source = $source;
        $this->codeDescription = $codeDescription;
        $this->tags = $tags;
        $this->relatedInformation = $relatedInformation;
        $this->data = $data;
    }
}
