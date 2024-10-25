<?php

namespace LanguageServerProtocol;

abstract class PrepareSupportDefaultBehavior
{
    /**
     * The client's default behavior is to select the identifier
     * according the to language's syntax rule.
     */
    const IDENTIFIER = 1;
}
