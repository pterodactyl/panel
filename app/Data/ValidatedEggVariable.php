<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

final readonly class ValidatedEggVariable
{
    public function __construct(
        public int $id,
        public string $key,
        public bool|float|int|string|null $value,
    ) {}
}
