<?php

namespace LanguageServerProtocol;

/**
 * Represents a location inside a resource, such as a line inside a text file.
 */
class Location
{
    /**
     * @var string
     */
    public $uri;

    /**
     * @var Range
     */
    public $range;

    public function __construct(string $uri = null, Range $range = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->uri = $uri;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->range = $range;
    }
}
