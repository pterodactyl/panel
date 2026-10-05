<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoMixedParameters;

use Closure;
use InvalidArgumentException;
use Throwable;

function nativeMixed(mixed $input): void {} // error: line 7

/**
 * @param  mixed  $payload
 */
function docblockMixed($payload): void {} // error: line 12

function typed(string $name, int $count): void {}

/**
 * @phpstan-assert string $value
 */
function assertString(mixed $value): void
{
    if (! is_string($value)) {
        throw new InvalidArgumentException('not a string');
    }
}

final class Widget
{
    public function handle(mixed $input): void {} // error: line 28

    public function typed(Throwable $previous): void {}
}

final class FrameworkValidationRule implements \Illuminate\Contracts\Validation\ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void {}
}

final class LegacyFrameworkValidationRule implements \Illuminate\Contracts\Validation\Rule
{
    public function passes($attribute, mixed $value): bool
    {
        return true;
    }

    public function message(): string
    {
        return 'invalid';
    }
}

interface Contract
{
    public function accept(mixed $value): void; // error: line 35
}

abstract class Base
{
    abstract public function consume(mixed $value): void; // error: line 40
}

$closure = function (mixed $thing): void {}; // error: line 43

$arrow = fn (mixed $item): int => 1; // error: line 45

$typedClosure = function (string $name): void {};
