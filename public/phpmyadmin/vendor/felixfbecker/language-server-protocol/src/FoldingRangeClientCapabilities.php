<?php

namespace LanguageServerProtocol;

class FoldingRangeClientCapabilities
{

    /**
     * Whether hover supports dynamic registration.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * The maximum number of folding ranges that the client prefers to receive
     * per document. The value serves as a hint, servers are free to follow the
     * limit.
     *
     * @var int|null
     */
    public $rangeLimit;

    /**
     * If set, the client signals that it only supports folding complete lines.
     * If set, client will ignore specified `startCharacter` and `endCharacter`
     * properties in a FoldingRange.
     *
     * @var bool|null
     */
    public $lineFoldingOnly;

    public function __construct(
        bool $dynamicRegistration = null,
        int $rangeLimit = null,
        bool $lineFoldingOnly = null
    ) {
        $this->dynamicRegistration = $dynamicRegistration;
        $this->rangeLimit = $rangeLimit;
        $this->lineFoldingOnly = $lineFoldingOnly;
    }
}
