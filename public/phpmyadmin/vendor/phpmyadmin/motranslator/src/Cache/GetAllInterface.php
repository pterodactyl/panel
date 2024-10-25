<?php

declare(strict_types=1);

namespace PhpMyAdmin\MoTranslator\Cache;

interface GetAllInterface
{
    /**
     * @return array<string, string>
     */
    public function getAll(): array;
}
