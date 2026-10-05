<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Services\Acl\Api\AdminAcl;

#[ResponseField('color', nullable: true)]
#[ResponseField('legacy_nest_id', 'integer', example: 1, nullable: true)]
class TagTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'eggs' => ['relation' => 'eggs', 'transformer' => EggTransformer::class, 'ability' => AdminAcl::RESOURCE_EGGS],
        'nodes' => ['relation' => 'nodes', 'transformer' => NodeTransformer::class, 'ability' => AdminAcl::RESOURCE_NODES],
    ];

    /**
     * Relationships that can be loaded onto this transformation.
     *
     * @var list<string>
     */
    protected array $availableIncludes = ['eggs', 'nodes'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Tag::RESOURCE_NAME;
    }

    /**
     * Transform a tag into its application API representation. Built-in game tags
     * report the name and colour that the Panel assigns them.
     *
     * @return ApiPayload
     */
    public function transform(Tag $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->effectiveName(),
            'slug' => $model->slug,
            'color' => $model->effectiveColor(),
            'is_predefined' => $model->isPredefined(),
            'legacy_nest_id' => $model->legacy_nest_id,
            'relationships' => [],
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Include the eggs that carry this tag.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeEggs(Tag $model): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $model->loadMissing('eggs');

        return $this->collection($model->getRelation('eggs'), $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }

    /**
     * Include the nodes that carry this tag, either as a game they accept or as a
     * deployment reservation.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeNodes(Tag $model): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $model->loadMissing('nodes');

        return $this->collection($model->getRelation('nodes'), $this->makeTransformer(NodeTransformer::class), Node::RESOURCE_NAME);
    }
}
