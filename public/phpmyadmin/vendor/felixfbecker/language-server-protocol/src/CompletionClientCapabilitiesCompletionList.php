<?php

namespace LanguageServerProtocol;

class CompletionClientCapabilitiesCompletionList
{

    /**
     * The client supports the the following itemDefaults on
     * a completion list.
     *
     * The value lists the supported property names of the
     * `CompletionList.itemDefaults` object. If omitted
     * no properties are supported.
     *
     * @since 3.17.0 - proposed state
     *
     * @var string[]|null
     */
    public $itemDefaults;

    /**
     * @param string[]|null $itemDefaults
     */
    public function __construct(
        array $itemDefaults = null
    ) {
        $this->itemDefaults = $itemDefaults;
    }
}
