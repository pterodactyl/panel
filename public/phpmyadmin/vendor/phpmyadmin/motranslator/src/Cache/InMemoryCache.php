<?php

declare(strict_types=1);

namespace PhpMyAdmin\MoTranslator\Cache;

use PhpMyAdmin\MoTranslator\MoParser;

use function array_key_exists;

final class InMemoryCache implements CacheInterface, GetAllInterface
{
    /** @var array<string, string> */
    private $cache;

    public function __construct(MoParser $parser)
    {
        $this->cache = [];
        $parser->parseIntoCache($this);
    }

    public function get(string $msgid): string
    {
        return array_key_exists($msgid, $this->cache) ? $this->cache[$msgid] : $msgid;
    }

    public function set(string $msgid, string $msgstr): void
    {
        $this->cache[$msgid] = $msgstr;
    }

    public function has(string $msgid): bool
    {
        return array_key_exists($msgid, $this->cache);
    }

    public function setAll(array $translations): void
    {
        $this->cache = $translations;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->cache;
    }
}
