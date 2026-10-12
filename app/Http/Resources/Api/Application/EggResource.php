<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Http\Resources\Api\NullResource;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Tag;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonEmptyObject;

/**
 * @extends ApplicationResource<Egg>
 */
#[ResponseField('description', nullable: true)]
#[ResponseField('docker_images', schema: ['type' => 'object', 'additionalProperties' => ['type' => 'string'], 'example' => ['Java 23' => 'ghcr.io/pterodactyl/yolks:java_23']])]
#[ResponseField('config.files', schema: ['type' => 'object', 'additionalProperties' => ['type' => 'object', 'properties' => ['parser' => ['type' => 'string'], 'find' => ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]]]]], 'example' => ['server.properties' => ['parser' => 'properties', 'find' => ['server-port' => '{{server.build.default.port}}']]]])]
#[ResponseField('config.logs', schema: ['type' => 'object', 'nullable' => true, 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean']]], 'example' => ['custom' => true, 'location' => 'logs/latest.log']])]
#[ResponseField('config.file_denylist', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['secret.txt']])]
#[ResponseField('config.stop', nullable: true)]
#[ResponseField('config.startup', schema: ['type' => 'object', 'nullable' => true, 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'array', 'items' => ['type' => 'string']]]], 'example' => ['done' => ['Done']]])]
class EggResource extends ApplicationResource
{
    protected array $includeRelations = [
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class, 'ability' => AdminAcl::RESOURCE_SERVERS],
        'config' => ['relation' => 'configFrom'],
        'script' => ['relation' => 'scriptFrom'],
        'variables' => ['relation' => 'variables', 'resource' => EggVariableResource::class, 'ability' => AdminAcl::RESOURCE_EGGS],
        'tags' => ['relation' => 'tags', 'resource' => TagResource::class, 'ability' => AdminAcl::RESOURCE_EGGS],
    ];

    protected array $availableIncludes = [
        'servers',
        'config',
        'script',
        'variables',
        'tags',
    ];

    public function resourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $egg = $this->resource;

        $files = json_decode($egg->config_files ?? 'null', true, 512, JSON_THROW_ON_ERROR);
        if (empty($files)) {
            $files = new JsonEmptyObject;
        }

        return [
            'id' => $egg->id,
            'uuid' => $egg->uuid,
            'name' => $egg->name,
            'author' => $egg->author,
            'description' => $egg->description,
            // "docker_image" is deprecated in favor of "docker_images"; kept for external compatibility.
            'docker_image' => count($egg->docker_images) > 0 ? Arr::first($egg->docker_images) : '',
            'docker_images' => $egg->docker_images,
            'config' => [
                'files' => $files,
                'startup' => json_decode($egg->config_startup ?? 'null', true),
                'stop' => $egg->config_stop,
                'logs' => json_decode($egg->config_logs ?? 'null', true),
                'file_denylist' => $egg->file_denylist,
                'extends' => $egg->config_from,
            ],
            'startup' => $egg->startup,
            'script' => [
                'privileged' => $egg->script_is_privileged,
                'install' => $egg->script_install,
                'entry' => $egg->script_entry,
                'container' => $egg->script_container,
                'extends' => $egg->copy_script_from,
            ],
            'relationships' => [],
            $egg->getCreatedAtColumn() => $this->formatTimestamp($egg->created_at),
            $egg->getUpdatedAtColumn() => $this->formatTimestamp($egg->updated_at),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'servers' => $this->includeServers(),
            'config' => $this->includeConfig(),
            'script' => $this->includeScript(),
            'variables' => $this->includeVariables(),
            'tags' => $this->includeTags(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeServers(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, Server::RESOURCE_NAME);
    }

    /**
     * @return ApiResource<Egg>|NullResource
     */
    private function includeConfig(): ApiResource|NullResource
    {
        if ($this->resource->config_from === null) {
            return $this->null();
        }

        $this->resource->loadMissing('configFrom');

        return $this->item($this->resource, fn (Egg $egg): array => [
            'files' => json_decode($egg->inherit_config_files ?? 'null'),
            'startup' => json_decode($egg->inherit_config_startup ?? 'null'),
            'stop' => $egg->inherit_config_stop,
            'logs' => json_decode($egg->inherit_config_logs ?? 'null'),
        ]);
    }

    /**
     * @return ApiResource<Egg>|NullResource
     */
    private function includeScript(): ApiResource|NullResource
    {
        if ($this->resource->copy_script_from === null) {
            return $this->null();
        }

        $this->resource->loadMissing('scriptFrom');

        return $this->item($this->resource, fn (Egg $egg): array => [
            'privileged' => $egg->script_is_privileged,
            'install' => $egg->copy_script_install,
            'entry' => $egg->copy_script_entry,
            'container' => $egg->copy_script_container,
        ]);
    }

    private function includeVariables(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('variables')->variables, EggVariableResource::class, EggVariable::RESOURCE_NAME);
    }

    private function includeTags(): ApiResourceCollection|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->items($this->resource->loadMissing('tags')->tags, TagResource::class, Tag::RESOURCE_NAME);
    }
}
