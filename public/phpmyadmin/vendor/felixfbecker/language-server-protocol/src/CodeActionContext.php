<?php

namespace LanguageServerProtocol;

/**
 * Contains additional diagnostic information about the context in which
 * a code action is run.
 */
class CodeActionContext
{
    /**
     * An array of diagnostics known on the client side overlapping the range
     * provided to the `textDocument/codeAction` request. They are provided so
     * that the server knows which errors are currently presented to the user
     * for the given range. There is no guarantee that these accurately reflect
     * the error state of the resource. The primary parameter
     * to compute code actions is the provided range.
     *
     * @var Diagnostic[]
     */
    public $diagnostics;

    /**
     * Requested kind of actions to return.
     *
     * Actions not of this kind are filtered out by the client before being
     * shown. So servers can omit computing them.
     *
     * @var string[]|null
     * @see CodeActionKind
     */
    public $only;

    /**
     * The reason why code actions were requested.
     *
     * @since 3.17.0
     *
     * @var int|null
     * @see CodeActionTriggerKind
     */
    public $triggerKind;

    /**
     * @param Diagnostic[] $diagnostics
     */
    public function __construct(array $diagnostics = [])
    {
        $this->diagnostics = $diagnostics;
    }
}
