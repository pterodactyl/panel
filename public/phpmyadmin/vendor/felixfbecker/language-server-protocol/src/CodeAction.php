<?php

namespace LanguageServerProtocol;

use JsonSerializable;

/**
 * A code action represents a change that can be performed in code, e.g. to fix
 * a problem or to refactor code.
 *
 * A CodeAction must set either `edit` and/or a `command`. If both are supplied,
 * the `edit` is applied first, then the `command` is executed.
 */
class CodeAction implements JsonSerializable
{
    /**
     * A short, human-readable, title for this code action.
     *
     * @var string
     */
    public $title;

    /**
     * The kind of the code action.
     *
     * Used to filter code actions.
     *
     * @var string|null
     * @see CodeActionKind
     */
    public $kind;

    /**
     * The diagnostics that this code action resolves.
     * @var Diagnostic[]|null
     */
    public $diagnostics;

    /**
     * Marks this as a preferred action. Preferred actions are used by the
     * `auto fix` command and can be targeted by keybindings.
     *
     * A quick fix should be marked preferred if it properly addresses the
     * underlying error. A refactoring should be marked preferred if it is the
     * most reasonable choice of actions to take.
     *
     * @since 3.15.0
     *
     * @var bool|null
     */
    public $isPreferred;

    /**
     * Marks that the code action cannot currently be applied.
     *
     * Clients should follow the following guidelines regarding disabled code
     * actions:
     *
     * - Disabled code actions are not shown in automatic lightbulbs code
     *   action menus.
     *
     * - Disabled actions are shown as faded out in the code action menu when
     *   the user request a more specific type of code action, such as
     *   refactorings.
     *
     * - If the user has a keybinding that auto applies a code action and only
     *   a disabled code actions are returned, the client should show the user
     *   an error message with `reason` in the editor.
     *
     * @since 3.16.0
     *
     * @var CodeActionDisabled|null
     */
    public $disabled;

    /**
     * The workspace edit this code action performs.
     *
     * @var WorkspaceEdit|null
     */
    public $edit;

    /**
     * A command this code action executes. If a code action
     * provides an edit and a command, first the edit is
     * executed and then the command.
     *
     * @var Command|null
     */
    public $command;

    /**
     * A data entry field that is preserved on a code action between
     * a `textDocument/codeAction` and a `codeAction/resolve` request.
     *
     * @since 3.16.0
     *
     * @var mixed|null
     */
    public $data;

    /**
     * Undocumented function
     *
     * @param string|null $title
     * @param string|null $kind
     * @param Diagnostic[]|null $diagnostics
     * @param boolean|null $isPreferred
     * @param CodeActionDisabled|null $disabled
     * @param WorkspaceEdit|null $edit
     * @param Command|null $command
     * @param mixed $data
     */
    public function __construct(
        string $title = null,
        string $kind = null,
        array $diagnostics = null,
        bool $isPreferred = null,
        CodeActionDisabled $disabled = null,
        WorkspaceEdit $edit = null,
        Command $command = null,
        $data = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->title = $title;
        $this->kind = $kind;
        $this->diagnostics = $diagnostics;
        $this->isPreferred = $isPreferred;
        $this->disabled = $disabled;
        $this->edit = $edit;
        $this->command = $command;
        $this->data = $data;
    }

    /**
     * This is needed because VSCode Does not like nulls
     * meaning if a null is sent then this will not compute
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}
