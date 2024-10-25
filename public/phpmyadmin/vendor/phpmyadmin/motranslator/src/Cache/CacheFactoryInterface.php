<?php

declare(strict_types=1);

namespace PhpMyAdmin\MoTranslator\Cache;

use PhpMyAdmin\MoTranslator\MoParser;

interface CacheFactoryInterface
{
    public function getInstance(MoParser $parser, string $locale, string $domain): CacheInterface;
}
