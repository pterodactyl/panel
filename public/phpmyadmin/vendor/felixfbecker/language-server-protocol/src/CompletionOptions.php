<?php

namespace LanguageServerProtocol;

/**
 * Completion options.
 */
class CompletionOptions
{
    /**
     * The server provides support to resolve additional information for a completion
     * item.
     *
     * @var bool|null
     */
    public $resolveProvider;

    /**
     * The characters that trigger completion automatically.
     *
     * @var string[]|null
     */
    public $triggerCharacters;

    /**
     * @param string[]|null $triggerCharacters
     */
    public function __construct(bool $resolveProvider = null, array $triggerCharacters = null)
    {
        $this->resolveProvider = $resolveProvider;
        $this->triggerCharacters = $triggerCharacters;
    }
}
