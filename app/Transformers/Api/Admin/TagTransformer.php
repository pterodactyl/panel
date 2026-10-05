<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;

#[ResponseField('legacy_nest_id', 'integer', example: 1, nullable: true)]
#[ResponseField('color', nullable: true)]
class TagTransformer extends BaseAdminTransformer
{
    protected array $includeRelations = [
        'eggs' => ['relation' => 'eggs', 'transformer' => EggTransformer::class],
        'nodes' => ['relation' => 'nodes', 'transformer' => NodeTransformer::class],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['eggs', 'nodes'];

    public function getResourceName(): string
    {
        return Tag::RESOURCE_NAME;
    }

    /**
     * The name and colour are the effective ones: a built-in game tag reports the
     * label and colour owned by the EggSpecificTags enum rather than whatever is
     * stored, so a client never has to know that distinction to render a tag.
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
            'eggs_count' => (int) ($model->eggs_count ?? 0),
            'nodes_count' => (int) ($model->nodes_count ?? 0),
            'created_at' => $this->formatTimestamp($model->created_at),
            'updated_at' => $this->formatTimestamp($model->updated_at),
        ];
    }

    public function includeEggs(Tag $model): Collection|NullResource
    {
        $model->loadMissing('eggs');

        return $this->collection(
            $model->getRelation('eggs'),
            $this->makeTransformer(EggTransformer::class),
            Egg::RESOURCE_NAME
        );
    }

    public function includeNodes(Tag $model): Collection|NullResource
    {
        $model->loadMissing('nodes');

        return $this->collection(
            $model->getRelation('nodes'),
            $this->makeTransformer(NodeTransformer::class),
            Node::RESOURCE_NAME
        );
    }
}
