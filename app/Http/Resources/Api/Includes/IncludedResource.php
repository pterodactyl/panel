<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Includes;

use Illuminate\Http\Request;
use JsonSerializable;

/**
 * What an include embeds in its parent's `relationships`: a resource, a list of them, or
 * the null resource. The parent resolves it where it is embedded as soon as it is included,
 * so includes load and authorize in the order Fractal ran them; encoding the response then
 * writes out what it resolved to, the way Laravel serializes nested resources.
 */
interface IncludedResource extends JsonSerializable
{
    public function resolveIncluded(IncludeScope $scope, Request $request): static;
}
