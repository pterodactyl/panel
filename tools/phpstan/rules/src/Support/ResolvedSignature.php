<?php

declare(strict_types=1);

namespace Rules\Support;

use PHPStan\Type\Type;

/**
 * A function-like signature with native and docblock types already combined,
 * shared by every rule that inspects parameter or return contracts.
 */
final class ResolvedSignature
{
    /**
     * @param  list<ResolvedParameter>  $parameters
     */
    public function __construct(
        public readonly array $parameters,
        public readonly Type $returnType,
        public readonly bool $hasAsserts,
        public readonly string $describedName,
    ) {}
}
