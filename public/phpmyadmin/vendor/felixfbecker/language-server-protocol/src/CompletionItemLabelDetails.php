<?php

namespace LanguageServerProtocol;

/**
 * Additional details for a completion item label.
 *
 * @since 3.17.0 - proposed state
 */
class CompletionItemLabelDetails
{
    /**
     * An optional string which is rendered less prominently directly after
     * {@link CompletionItem.label label}, without any spacing. Should be
     * used for function signatures or type annotations.
     *
     * @var string|null
     */
    public $detail;

    /**
     * An optional string which is rendered less prominently after
     * {@link CompletionItemLabelDetails.detail}. Should be used for fully qualified
     * names or file path.
     *
     * @var string|null
     */
    public $description;

    public function __construct(string $detail = null, string $description = null)
    {
        $this->detail = $detail;
        $this->description = $description;
    }
}
