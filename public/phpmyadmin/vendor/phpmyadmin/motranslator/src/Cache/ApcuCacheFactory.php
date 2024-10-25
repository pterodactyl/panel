<?php

declare(strict_types=1);

namespace PhpMyAdmin\MoTranslator\Cache;

use PhpMyAdmin\MoTranslator\MoParser;

final class ApcuCacheFactory implements CacheFactoryInterface
{
    /** @var int */
    private $ttl;
    /** @var bool */
    private $reloadOnMiss;
    /** @var string */
    private $prefix;

    public function __construct(int $ttl = 0, bool $reloadOnMiss = true, string $prefix = 'mo_')
    {
        $this->ttl = $ttl;
        $this->reloadOnMiss = $reloadOnMiss;
        $this->prefix = $prefix;
    }

    public function getInstance(MoParser $parser, string $locale, string $domain): CacheInterface
    {
        return new ApcuCache($parser, $locale, $domain, $this->ttl, $this->reloadOnMiss, $this->prefix);
    }
}
