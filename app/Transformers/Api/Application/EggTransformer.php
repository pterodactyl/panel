<?php

namespace Pterodactyl\Transformers\Api\Application;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Server;
use League\Fractal\Resource\Item;
use Pterodactyl\Models\EggVariable;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class EggTransformer extends BaseTransformer
{
    /**
     * Relationships that can be loaded onto this transformation.
     */
    protected array $availableIncludes = [
        'nest',
        'servers',
        'config',
        'script',
        'variables',
    ];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    /**
     * Transform an Egg model into a representation that can be consumed by
     * the application api.
     *
     * @throws \JsonException
     */
    public function transform(Egg $model): array
    {
        $files = json_decode($model->config_files, true, 512, JSON_THROW_ON_ERROR);
        if (empty($files)) {
            $files = new \stdClass();
        }

        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'nest' => $model->nest_id,
            'author' => $model->author,
            'description' => $model->description,
            // "docker_image" is deprecated, but left here to avoid breaking too many things at once
            // in external software. We'll remove it down the road once things have gotten the chance
            // to upgrade to using "docker_images".
            'docker_image' => count($model->docker_images) > 0 ? Arr::first($model->docker_images) : '',
            'docker_images' => $model->docker_images,
            'config' => [
                'files' => $files,
                'startup' => json_decode($model->config_startup, true),
                'stop' => $model->config_stop,
                'logs' => json_decode($model->config_logs, true),
                'file_denylist' => $model->file_denylist,
                'extends' => $model->config_from,
            ],
            'startup' => $model->startup,
            'script' => [
                'privileged' => $model->script_is_privileged,
                'install' => $model->script_install,
                'entry' => $model->script_entry,
                'container' => $model->script_container,
                'extends' => $model->copy_script_from,
            ],
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Include the Nest relationship for the given Egg in the transformation.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeNest(Egg $model): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NESTS)) {
            return $this->null();
        }

        $model->loadMissing('nest');

        return $this->item($model->getRelation('nest'), $this->makeTransformer(NestTransformer::class), Nest::RESOURCE_NAME);
    }

    /**
     * Include the Servers relationship for the given Egg in the transformation.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeServers(Egg $model): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $model->loadMissing('servers');

        return $this->collection($model->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), Server::RESOURCE_NAME);
    }

    /**
     * Include more detailed information about the configuration if this Egg is
     * extending another.
     */
    public function includeConfig(Egg $model): Item|NullResource
    {
        if (is_null($model->config_from)) {
            return $this->null();
        }

        $model->loadMissing('configFrom');

        return $this->item($model, function (Egg $model) {
            return [
                'files' => json_decode($model->inherit_config_files),
                'startup' => json_decode($model->inherit_config_startup),
                'stop' => $model->inherit_config_stop,
                'logs' => json_decode($model->inherit_config_logs),
            ];
        });
    }

    /**
     * Include more detailed information about the script configuration if the
     * Egg is extending another.
     */
    public function includeScript(Egg $model): Item|NullResource
    {
        if (is_null($model->copy_script_from)) {
            return $this->null();
        }

        $model->loadMissing('scriptFrom');

        return $this->item($model, function (Egg $model) {
            return [
                'privileged' => $model->script_is_privileged,
                'install' => $model->copy_script_install,
                'entry' => $model->copy_script_entry,
                'container' => $model->copy_script_container,
            ];
        });
    }

    /**
     * Include the variables that are defined for this Egg.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeVariables(Egg $model): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $model->loadMissing('variables');

        return $this->collection(
            $model->getRelation('variables'),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }
}
