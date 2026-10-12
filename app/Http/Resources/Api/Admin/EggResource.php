<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Includes\IncludedResource;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Tag;
use Pterodactyl\Support\JsonEmptyObject;

/**
 * @extends AdminResource<Egg>
 */
#[ResponseField('description', nullable: true)]
#[ResponseField('features', nullable: true)]
#[ResponseField('docker_images', schema: ['type' => 'object', 'additionalProperties' => ['type' => 'string'], 'example' => ['Java 23' => 'ghcr.io/pterodactyl/yolks:java_23']])]
#[ResponseField('config.files', nullable: true, schema: ['type' => 'object', 'additionalProperties' => ['type' => 'object', 'properties' => ['parser' => ['type' => 'string'], 'find' => ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]]]]], 'example' => ['server.properties' => ['parser' => 'properties', 'find' => ['server-port' => '{{server.build.default.port}}']]]])]
#[ResponseField('config.logs', nullable: true, schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'number'], ['type' => 'boolean']]], 'example' => ['custom' => true, 'location' => 'logs/latest.log']])]
#[ResponseField('config.startup', nullable: true, schema: ['type' => 'object', 'additionalProperties' => ['oneOf' => [['type' => 'string'], ['type' => 'array', 'items' => ['type' => 'string']]]], 'example' => ['done' => ['Done']]])]
#[ResponseField('config.file_denylist', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['secret.txt']])]
#[ResponseField('force_outgoing_ip', 'boolean', nullable: true)]
#[ResponseField('script.privileged', 'boolean', nullable: true)]
class EggResource extends AdminResource
{
    protected array $includeRelations = [
        'variables' => ['relation' => 'variables', 'resource' => EggVariableResource::class],
        'tags' => ['relation' => 'tags', 'resource' => TagResource::class],
        'servers' => ['relation' => 'servers', 'resource' => ServerResource::class],
    ];

    protected array $availableIncludes = ['variables', 'tags', 'servers'];

    public function resourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        $egg = $this->resource;

        return [
            'id' => $egg->id,
            'uuid' => $egg->uuid,
            'author' => $egg->author,
            'name' => $egg->name,
            'description' => $egg->description,
            'features' => $egg->features,
            // "docker_image" is deprecated in favor of "docker_images"; kept for external compatibility.
            'docker_image' => count($egg->docker_images) > 0 ? Arr::first($egg->docker_images) : '',
            'docker_images' => $egg->docker_images,
            'force_outgoing_ip' => $egg->force_outgoing_ip,
            'config' => [
                'files' => $this->configuration($egg->config_files),
                'startup' => $this->configuration($egg->config_startup),
                'stop' => $egg->config_stop,
                'logs' => $this->configuration($egg->config_logs),
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
            ...$this->extensionFields($egg),
        ];
    }

    protected function resolveInclude(string $include): ?IncludedResource
    {
        return match ($include) {
            'variables' => $this->includeVariables(),
            'tags' => $this->includeTags(),
            'servers' => $this->includeServers(),
            default => parent::resolveInclude($include),
        };
    }

    private function includeVariables(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('variables')->variables, EggVariableResource::class, EggVariable::RESOURCE_NAME);
    }

    private function includeTags(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('tags')->tags, TagResource::class, Tag::RESOURCE_NAME);
    }

    private function includeServers(): ApiResourceCollection
    {
        return $this->items($this->resource->loadMissing('servers')->servers, ServerResource::class, Server::RESOURCE_NAME);
    }

    /**
     * A configuration column exactly as stored. Null means the egg inherits the value from the
     * egg it copies configuration from, while "{}" means it has none, so neither may turn into
     * the other on the way to the editor and back.
     */
    private function configuration(?string $encoded): mixed
    {
        if ($encoded === null) {
            return null;
        }

        $value = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);

        // Decoding to arrays reads "{}" as an empty list, which would reach the editor as "[]".
        return $value === [] && str_starts_with(mb_ltrim($encoded), '{') ? new JsonEmptyObject : $value;
    }
}
