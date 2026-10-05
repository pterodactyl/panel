<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Mount;

#[ResponseField('mounted', 'boolean', 'Present only when listed in the context of a server.', required: false, nullable: true)]
class MountTransformer extends BaseAdminTransformer
{
    protected array $includeRelations = [
        'eggs' => ['relation' => 'eggs', 'transformer' => EggTransformer::class],
        'nodes' => ['relation' => 'nodes', 'transformer' => NodeTransformer::class],
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class],
    ];

    // Only list includes with an implemented include*() method; Fractal would 500 on an unimplemented one.
    /**
     * @var list<string>
     */
    protected array $availableIncludes = [
        'eggs',
        'nodes',
        'servers',
    ];

    public function getResourceName(): string
    {
        return Mount::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(Mount $mount): array
    {
        return [
            'id' => $mount->id,
            'uuid' => $mount->uuid,
            'name' => $mount->name,
            'description' => $mount->description,
            'source' => $mount->source,
            'target' => $mount->target,
            'read_only' => (bool) $mount->read_only,
            'user_mountable' => (bool) $mount->user_mountable,
            'eggs_count' => (int) ($mount->eggs_count ?? 0),
            'nodes_count' => (int) ($mount->nodes_count ?? 0),
            'servers_count' => (int) ($mount->servers_count ?? 0),
        ];
    }

    public function includeEggs(Mount $mount): Collection|NullResource
    {
        $mount->loadMissing('eggs');

        return $this->collection(
            $mount->getRelation('eggs'),
            $this->makeTransformer(EggTransformer::class),
            'egg'
        );
    }

    public function includeNodes(Mount $mount): Collection|NullResource
    {
        $mount->loadMissing('nodes');

        return $this->collection(
            $mount->getRelation('nodes'),
            $this->makeTransformer(NodeTransformer::class),
            'node'
        );
    }

    public function includeServers(Mount $mount): Collection|NullResource
    {
        $mount->loadMissing('servers');

        return $this->collection(
            $mount->getRelation('servers'),
            $this->makeTransformer(ServerTransformer::class),
            'server'
        );
    }
}
