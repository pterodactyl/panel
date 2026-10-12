<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @extends ApplicationResource<Tag>
 */
#[ResponseField('color', nullable: true)]
#[ResponseField('legacy_nest_id', 'integer', example: 1, nullable: true)]
class TagResource extends ApplicationResource
{
    protected array $includeRelations = [
        'eggs' => ['relation' => 'eggs', 'resource' => EggResource::class, 'ability' => AdminAcl::RESOURCE_EGGS],
        'nodes' => ['relation' => 'nodes', 'resource' => NodeResource::class, 'ability' => AdminAcl::RESOURCE_NODES],
    ];

    protected array $availableIncludes = ['eggs', 'nodes'];

    public function resourceName(): string
    {
        return Tag::RESOURCE_NAME;
    }

    /**
     * Built-in game tags report the name and colour that the Panel assigns them.
     */
    public function toArray(Request $request): array
    {
        $tag = $this->resource;

        return [
            'id' => $tag->id,
            'name' => $tag->effectiveName(),
            'slug' => $tag->slug,
            'color' => $tag->effectiveColor(),
            'is_predefined' => $tag->isPredefined(),
            'legacy_nest_id' => $tag->legacy_nest_id,
            'relationships' => [],
            $tag->getCreatedAtColumn() => $this->formatTimestamp($tag->created_at),
            $tag->getUpdatedAtColumn() => $this->formatTimestamp($tag->updated_at),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'eggs' => $this->includeEggs(),
            'nodes' => $this->includeNodes(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeEggs(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('eggs')->eggs, EggResource::class, Egg::RESOURCE_NAME);
    }

    private function includeNodes(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('nodes')->nodes, NodeResource::class, Node::RESOURCE_NAME);
    }
}
