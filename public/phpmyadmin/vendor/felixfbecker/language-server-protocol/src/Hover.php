<?php

namespace LanguageServerProtocol;

/**
 * The result of a hover request.
 */
class Hover
{
    /**
     * The hover's content
     *
     * @var string|MarkedString|string[]|MarkedString[]|MarkupContent
     */
    public $contents;

    /**
     * An optional range
     *
     * @var Range|null
     */
    public $range;

    /**
     * @param string|MarkedString|string[]|MarkedString[]|MarkupContent $contents The hover's content
     * @param Range $range An optional range
     */
    public function __construct($contents = null, $range = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->contents = $contents;
        $this->range = $range;
    }
}
