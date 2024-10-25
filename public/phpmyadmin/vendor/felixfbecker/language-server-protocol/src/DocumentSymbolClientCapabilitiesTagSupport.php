<?php

namespace LanguageServerProtocol;

class DocumentSymbolClientCapabilitiesTagSupport
{

    /**
     * The symbol kind values the client supports. When this
     * property exists the client also guarantees that it will
     * handle values outside its set gracefully and falls back
     * to a default value when unknown.
     *
     * If this property is not present the client only supports
     * the symbol kinds from `File` to `Array` as defined in
     * the initial version of the protocol.
     *
     * @var int[]
     */
    public $valueSet;

    /**
     * Undocumented function
     *
     * @param int[]|null $valueSet
     */
    public function __construct(array $valueSet = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->valueSet = $valueSet;
    }
}
