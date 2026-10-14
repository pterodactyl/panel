<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Includes;

/**
 * An include counts as requested only when its full dotted path from the root was requested.
 */
final readonly class IncludeScope
{
    /**
     * @param  list<string>  $path
     */
    public function __construct(private IncludeSet $includes, private array $path = []) {}

    public function isRequested(string $include): bool
    {
        return $this->includes->contains(implode('.', [...$this->path, $include]));
    }

    public function child(string $include): self
    {
        return new self($this->includes, [...$this->path, $include]);
    }

    public function depth(): int
    {
        return count($this->path);
    }
}
