<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use Illuminate\Support\Arr;
use JsonException;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Tag;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Support\JsonValueGuard;

#[ResponseField('description', nullable: true)]
#[ResponseField('docker_images', schema: ['type' => 'object', 'additionalProperties' => ['type' => 'string'], 'example' => ['Java 23' => 'ghcr.io/pterodactyl/yolks:java_23']])]
#[ResponseField('config.files', schema: ['type' => 'object', 'additionalProperties' => ['type' => 'object', 'properties' => ['parser' => ['type' => 'string'], 'find' => ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]]]]], 'example' => ['server.properties' => ['parser' => 'properties', 'find' => ['server-port' => '{{server.build.default.port}}']]]])]
#[ResponseField('config.logs', schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean']]], 'example' => ['custom' => true, 'location' => 'logs/latest.log']])]
#[ResponseField('config.file_denylist', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['secret.txt']])]
#[ResponseField('config.startup', schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'array', 'items' => ['type' => 'string']]]], 'example' => ['done' => ['Done']]])]
class EggTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'transformer' => ServerTransformer::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
        'config' => ['relation' => 'configFrom'],
        'script' => ['relation' => 'scriptFrom'],
        'variables' => ['relation' => 'variables', 'transformer' => EggVariableTransformer::class, 'ability' => AdminAcl::RESOURCE_EGGS],
        'tags' => ['relation' => 'tags', 'transformer' => TagTransformer::class, 'ability' => AdminAcl::RESOURCE_EGGS],
    ];

    /**
     * Relationships that can be loaded onto this transformation.
     *
     * @var list<string>
     */
    protected array $availableIncludes = [
        'servers',
        'config',
        'script',
        'variables',
        'tags',
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
     * @return ApiPayload
     *
     * @throws JsonException
     */
    public function transform(Egg $model): array
    {
        $files = json_decode($model->config_files ?? 'null', true, 512, JSON_THROW_ON_ERROR);
        if (empty($files)) {
            $files = new JsonEmptyObject;
        }

        $payload = [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'author' => $model->author,
            'description' => $model->description,
            // "docker_image" is deprecated, but left here to avoid breaking too many things at once
            // in external software. We'll remove it down the road once things have gotten the chance
            // to upgrade to using "docker_images".
            'docker_image' => count($model->docker_images) > 0 ? Arr::first($model->docker_images) : '',
            'docker_images' => $model->docker_images,
            'config' => [
                'files' => $files,
                'startup' => json_decode($model->config_startup ?? 'null', true),
                'stop' => $model->config_stop,
                'logs' => json_decode($model->config_logs ?? 'null', true),
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
            'relationships' => [],
            $model->getCreatedAtColumn() => $this->formatTimestamp($model->created_at),
            $model->getUpdatedAtColumn() => $this->formatTimestamp($model->updated_at),
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }

    /**
     * Include the Servers relationship for the given Egg in the transformation.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeServers(Egg $model): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
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
        if (($model->config_from) === null) {
            return $this->null();
        }

        $model->loadMissing('configFrom');

        return $this->item($model, fn (Egg $model): array => [
            'files' => json_decode($model->inherit_config_files ?? 'null'),
            'startup' => json_decode($model->inherit_config_startup ?? 'null'),
            'stop' => $model->inherit_config_stop,
            'logs' => json_decode($model->inherit_config_logs ?? 'null'),
        ]);
    }

    /**
     * Include more detailed information about the script configuration if the
     * Egg is extending another.
     */
    public function includeScript(Egg $model): Item|NullResource
    {
        if (($model->copy_script_from) === null) {
            return $this->null();
        }

        $model->loadMissing('scriptFrom');

        return $this->item($model, fn (Egg $model): array => [
            'privileged' => $model->script_is_privileged,
            'install' => $model->copy_script_install,
            'entry' => $model->copy_script_entry,
            'container' => $model->copy_script_container,
        ]);
    }

    /**
     * Include the variables that are defined for this Egg.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeVariables(Egg $model): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $model->loadMissing('variables');

        return $this->collection(
            $model->getRelation('variables'),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }

    /**
     * Include the tags assigned to this Egg.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeTags(Egg $model): Collection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $model->loadMissing('tags');

        return $this->collection($model->getRelation('tags'), $this->makeTransformer(TagTransformer::class), Tag::RESOURCE_NAME);
    }
}
