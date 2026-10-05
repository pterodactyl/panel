<?php

declare(strict_types=1);

namespace Rules\Support;

use PHPStan\Type\Type;

/**
 * One parameter of a resolved function-like signature: the declared name, the
 * combined native and docblock type, and whether a `@phpstan-assert` family tag
 * targets it (which marks the enclosing function as a boundary parser).
 */
final class ResolvedParameter
{
    public function __construct(
        public readonly string $name,
        public readonly Type $type,
        public readonly bool $hasAssertTag,
    ) {}
}
