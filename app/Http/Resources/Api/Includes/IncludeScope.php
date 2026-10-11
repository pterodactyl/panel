<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Includes;

/**
 * Where a resource sits in the response: the include path from the root resource to it.
 * An include counts as requested when its full dotted path from the root was requested,
 * as with Fractal's Scope::isRequested().
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

    /**
     * The scope of a resource embedded under $include.
     */
    public function child(string $include): self
    {
        return new self($this->includes, [...$this->path, $include]);
    }

    /**
     * How many includes deep this scope is; the root resource is at depth 0.
     */
    public function depth(): int
    {
        return count($this->path);
    }
}
