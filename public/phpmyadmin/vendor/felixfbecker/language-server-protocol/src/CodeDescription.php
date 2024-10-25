<?php

namespace LanguageServerProtocol;

/**
 * Structure to capture a description for an error code.
 *
 * @since 3.16.0
 */
class CodeDescription
{
 /**
     * An URI to open with more information about the diagnostic error.
     *
     * @var string
     */
    public $href;

    public function __construct(string $href)
    {
        $this->href = $href;
    }
}
