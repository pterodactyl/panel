<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api;

use Illuminate\Http\Request;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\Includes\IncludeScope;

final class NullResource implements IncludedResource
{
    public function resolveIncluded(IncludeScope $scope, Request $request): static
    {
        return $this;
    }

    /**
     * @return array{object: 'null_resource', attributes: null}
     */
    public function jsonSerialize(): array
    {
        return ['object' => 'null_resource', 'attributes' => null];
    }
}
