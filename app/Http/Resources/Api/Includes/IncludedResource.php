<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Includes;

use Illuminate\Http\Request;
use JsonSerializable;

/**
 * The parent resolves an include as soon as it is included, so includes load and authorize
 * in include order; encoding the response then writes out what it resolved to.
 */
interface IncludedResource extends JsonSerializable
{
    public function resolveIncluded(IncludeScope $scope, Request $request): static;
}
