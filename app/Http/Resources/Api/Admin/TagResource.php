<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;

/**
 * @extends AdminResource<Tag>
 */
#[ResponseField('legacy_nest_id', 'integer', example: 1, nullable: true)]
#[ResponseField('color', nullable: true)]
class TagResource extends AdminResource
{
    protected array $includeRelations = [
        'eggs' => ['relation' => 'eggs', 'resource' => EggResource::class],
        'nodes' => ['relation' => 'nodes', 'resource' => NodeResource::class],
    ];

    protected array $availableIncludes = ['eggs', 'nodes'];

    public function resourceName(): string
    {
        return Tag::RESOURCE_NAME;
    }

    /**
     * The name and colour are the effective ones: a built-in game tag reports the
     * label and colour owned by the EggSpecificTags enum rather than whatever is
     * stored, so a client never has to know that distinction to render a tag.
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
            'eggs_count' => (int) ($tag->eggs_count ?? 0),
            'nodes_count' => (int) ($tag->nodes_count ?? 0),
            'created_at' => $this->formatTimestamp($tag->created_at),
            'updated_at' => $this->formatTimestamp($tag->updated_at),
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

    private function includeEggs(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('eggs')->eggs, EggResource::class, Egg::RESOURCE_NAME);
    }

    private function includeNodes(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('nodes')->nodes, NodeResource::class, Node::RESOURCE_NAME);
    }
}
