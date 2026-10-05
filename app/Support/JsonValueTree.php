<?php

declare(strict_types=1);

namespace Pterodactyl\Support;

use Closure;
use JsonSerializable;

final readonly class JsonValueTree implements JsonSerializable
{
    /** @param ApiScalar|JsonEmptyObject|array<array-key, self> $value */
    private function __construct(private bool|float|int|string|array|JsonEmptyObject|null $value) {}

    /** @param JsonInputValue $value */
    public static function from(mixed $value): self
    {
        if (! is_array($value)) {
            return new self($value);
        }

        $children = [];
        foreach ($value as $key => $child) {
            $children[$key] = self::from($child);
        }

        return new self($children);
    }

    /** @param Closure(string): string $mapper */
    public function mapStrings(Closure $mapper): self
    {
        if (is_string($this->value)) {
            return new self($mapper($this->value));
        }

        if (! is_array($this->value)) {
            return $this;
        }

        $children = [];
        foreach ($this->value as $key => $child) {
            $children[$key] = $child->mapStrings($mapper);
        }

        return new self($children);
    }

    public function arraySize(): ?int
    {
        return is_array($this->value) ? count($this->value) : null;
    }

    /** @return JsonValue */
    public function toValue(): bool|float|int|string|array|null
    {
        return JsonValueGuard::decode(json_encode($this, JSON_THROW_ON_ERROR));
    }

    /** @return ApiValue8 */
    public function toValue8(): bool|float|int|string|array|null
    {
        return JsonValueGuard::decode8(json_encode($this, JSON_THROW_ON_ERROR));
    }

    /** @return array<array-key, ApiValue7> */
    public function toArray8(): array
    {
        return JsonValueGuard::decodeArray8(json_encode($this, JSON_THROW_ON_ERROR));
    }

    /** @return ApiScalar|JsonEmptyObject|array<array-key, self> */
    public function jsonSerialize(): bool|float|int|string|array|JsonEmptyObject|null
    {
        return $this->value;
    }
}
