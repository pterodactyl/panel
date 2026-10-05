<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoMixedTypeAliases;

/**
 * @phpstan-type ExternalValue mixed
 * @phpstan-type Payload array{id: int, meta: mixed}
 * @phpstan-type MaybeMixed string|mixed
 * @psalm-type LoosePsalm mixed
 * @phpstan-type Hidden ExternalValue
 * @phpstan-type Named array{name: string}
 */
final class Holder // errors: ExternalValue, MaybeMixed, LoosePsalm, Hidden — all at line 16
{
    public function noop(): void {}
}

/**
 * @phpstan-type Row array{id: int, label: string}
 */
final class CleanHolder
{
    public function noop(): void {}
}
