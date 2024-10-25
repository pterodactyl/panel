<?php
declare(strict_types = 1);

namespace LanguageServerProtocol;

class CompletionItem
{
    /**
     * The label of this completion item. By default
     * also the text that is inserted when selecting
     * this completion.
     *
     * @var string
     */
    public $label;

    /**
     * Additional details for the label
     *
     * @since 3.17.0 - proposed state
     *
     * @var CompletionItemLabelDetails|null
     */
    public $labelDetails;

    /**
     * The kind of this completion item. Based of the kind
     * an icon is chosen by the editor.
     *
     * @var int|null
     * @see CompletionItemKind
     */
    public $kind;

    /**
     * Tags for this completion item.
     *
     * @since 3.15.0
     *
     * @var CompletionItemTag[]|null
     */
    public $tags;

    /**
     * A human-readable string with additional information
     * about this item, like type or symbol information.
     *
     * @var string|null
     */
    public $detail;

    /**
     * A human-readable string that represents a doc-comment.
     *
     * @var string|null
     */
    public $documentation;

    /**
     * Indicates if this item is deprecated.
     *
     * @deprecated Use `tags` instead if supported.
     *
     * @var bool|null
     */
    public $deprecated;

    /**
     * Select this item when showing.
     *
     * *Note* that only one completion item can be selected and that the
     * tool / client decides which item that is. The rule is that the *first*
     * item of those that match best is selected.
     *
     * @var bool|null
     */
    public $preselect;

    /**
     * A string that should be used when comparing this item
     * with other items. When `falsy` the label is used.
     *
     * @var string|null
     */
    public $sortText;

    /**
     * A string that should be used when filtering a set of
     * completion items. When `falsy` the label is used.
     *
     * @var string|null
     */
    public $filterText;

    /**
     * A string that should be inserted into a document when selecting
     * this completion. When `falsy` the label is used as the insert text
     * for this item.
     *
     * The `insertText` is subject to interpretation by the client side.
     * Some tools might not take the string literally. For example
     * VS Code when code complete is requested in this example
     * `con<cursor position>` and a completion item with an `insertText` of
     * `console` is provided it will only insert `sole`. Therefore it is
     * recommended to use `textEdit` instead since it avoids additional client
     * side interpretation.
     *
     * @var string|null
     */
    public $insertText;

    /**
     * The format of the insert text. The format applies to both the
     * `insertText` property and the `newText` property of a provided
     * `textEdit`. If omitted defaults to `InsertTextFormat.PlainText`.
     *
     * Please note that the insertTextFormat doesn't apply to
     * `additionalTextEdits`.
     *
     * @var int|null
     * @see InsertTextFormat
     */
    public $insertTextFormat;

    /**
     * How whitespace and indentation is handled during completion
     * item insertion. If not provided the client's default value depends on
     * the `textDocument.completion.insertTextMode` client capability.
     *
     * @since 3.16.0
     * @since 3.17.0 - support for `textDocument.completion.insertTextMode`
     *
     * @var int|null
     * @see InsertTextMode
     */
    public $insertTextMode;

    /**
     * An edit which is applied to a document when selecting this completion.
     * When an edit is provided the value of `insertText` is ignored.
     *
     * *Note:* The range of the edit must be a single line range and it must
     * contain the position at which completion has been requested.
     *
     * Most editors support two different operations when accepting a completion
     * item. One is to insert a completion text and the other is to replace an
     * existing text with a completion text. Since this can usually not be
     * predetermined by a server it can report both ranges. Clients need to
     * signal support for `InsertReplaceEdit`s via the
     * `textDocument.completion.completionItem.insertReplaceSupport` client
     * capability property.
     *
     * *Note 1:* The text edit's range as well as both ranges from an insert
     * replace edit must be a [single line] and they must contain the position
     * at which completion has been requested.
     * *Note 2:* If an `InsertReplaceEdit` is returned the edit's insert range
     * must be a prefix of the edit's replace range, that means it must be
     * contained and starting at the same position.
     *
     * @since 3.16.0 additional type `InsertReplaceEdit`
     *
     * @var TextEdit|null
     */
    public $textEdit;

    /**
     * An optional array of additional text edits that are applied when
     * selecting this completion. Edits must not overlap (including the same
     * insert position) with the main edit nor with themselves.
     *
     * Additional text edits should be used to change text unrelated to the
     * current cursor position (for example adding an import statement at the
     * top of the file if the completion item will insert an unqualified type).
     *
     * @var TextEdit[]|null
     */
    public $additionalTextEdits;

    /**
     * An optional set of characters that when pressed while this completion is
     * active will accept it first and then type that character. *Note* that all
     * commit characters should have `length=1` and that superfluous characters
     * will be ignored.
     *
     * @var string[]|null
     */
    public $commitCharacters;

    /**
     * An optional command that is executed *after* inserting this completion. *Note* that
     * additional modifications to the current document should be described with the
     * additionalTextEdits-property.
     *
     * @var Command|null
     */
    public $command;

    /**
     * An data entry field that is preserved on a completion item between
     * a completion and a completion resolve request.
     *
     * @var mixed
     */
    public $data;

    /**
     * @param string          $label
     * @param int|null        $kind
     * @param string|null     $detail
     * @param string|null     $documentation
     * @param string|null     $sortText
     * @param string|null     $filterText
     * @param string|null     $insertText
     * @param TextEdit|null   $textEdit
     * @param TextEdit[]|null $additionalTextEdits
     * @param Command|null    $command
     * @param mixed|null      $data
     * @param int|null        $insertTextFormat
     */
    public function __construct(
        string $label = null,
        int $kind = null,
        string $detail = null,
        string $documentation = null,
        string $sortText = null,
        string $filterText = null,
        string $insertText = null,
        TextEdit $textEdit = null,
        array $additionalTextEdits = null,
        Command $command = null,
        $data = null,
        int $insertTextFormat = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->label = $label;
        $this->kind = $kind;
        $this->detail = $detail;
        $this->documentation = $documentation;
        $this->sortText = $sortText;
        $this->filterText = $filterText;
        $this->insertText = $insertText;
        $this->textEdit = $textEdit;
        $this->additionalTextEdits = $additionalTextEdits;
        $this->command = $command;
        $this->data = $data;
        $this->insertTextFormat = $insertTextFormat;
    }
}
