<?php

declare(strict_types=1);

namespace PhpMyAdmin\MoTranslator\Cache;

interface CacheInterface
{
    /**
     * Returns cached `msgstr` if it is in cache, otherwise `$msgid`
     */
    public function get(string $msgid): string;

    /**
     * Caches `$msgstr` value for key `$mesid`
     */
    public function set(string $msgid, string $msgstr): void;

    /**
     * Returns true if cache has entry for `$msgid`
     */
    public function has(string $msgid): bool;

    /**
     * Populates cache with array of `$msgid => $msgstr` entries
     *
     * This will overwrite existing values for `$msgid`, but is not guaranteed to clear cache of existing entries
     * not present in `$translations`.
     *
     * @param array<string, string> $translations
     */
    public function setAll(array $translations): void;
}
