<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Egg;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class EggTransformer extends Transformer
{
    /**
     * Relationships that can be loaded onto this transformation.
     */
    protected array $availableIncludes = [
        'config',
        'nest',
        'script',
        'servers',
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
            'nest_id' => $model->nest_id,
            'author' => $model->author,
            'description' => $model->description,
            'docker_images' => $model->docker_images,
            'config' => [
                'files' => $files,
                'startup' => json_decode($model->config_startup, true),
                'stop' => $model->config_stop,
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
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
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

        return $this->item($model, function (Egg $model) {
            return [
                'files' => json_decode($model->inherit_config_files),
                'startup' => json_decode($model->inherit_config_startup),
                'stop' => $model->inherit_config_stop,
            ];
        });
    }

    /**
     * Include the Nest relationship for the given Egg in the transformation.
     */
    public function includeNest(Egg $model): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NESTS)) {
            return $this->null();
        }

        return $this->item($model->nest, new NestTransformer());
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
     * Include the Servers relationship for the given Egg in the transformation.
     */
    public function includeServers(Egg $model): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($model->servers, new ServerTransformer());
    }

    /**
     * Include the variables that are defined for this Egg.
     */
    public function includeVariables(Egg $model): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $model->loadMissing('variables');

        return $this->collection($model->variables, new EggVariableTransformer());
    }
}
