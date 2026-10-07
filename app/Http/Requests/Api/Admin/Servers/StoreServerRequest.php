<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Pterodactyl\Data\ServerCreationData;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\FiltersDeployTags;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\AllocationRules;
use Pterodactyl\Validation\ServerRules;

class StoreServerRequest extends AdminApiRequest
{
    use FiltersDeployTags;
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminServersCreate];
    }

    /**
     * Validation rules for creating a server.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ServerRules::rules();

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'description' => array_merge(['nullable'], $rules['description']),
            'owner_id' => $rules['owner_id'],
            'egg_id' => $rules['egg_id'],
            'docker_image' => $rules['image'],
            'startup' => $rules['startup'],
            'environment' => ['present', 'array'],
            'skip_scripts' => ['sometimes', 'boolean'],
            'oom_disabled' => ['sometimes', 'boolean'],

            'memory' => $rules['memory'],
            'swap' => $rules['swap'],
            'disk' => $rules['disk'],
            'io' => $rules['io'],
            'threads' => $rules['threads'],
            'cpu' => $rules['cpu'],

            'database_limit' => $rules['database_limit'],
            'allocation_limit' => $rules['allocation_limit'],
            'backup_limit' => $rules['backup_limit'],

            // Allocations are chosen by hand unless a deployment object picks them.
            'primary_allocation_id' => ['exclude_with:deploy', 'required', 'integer', 'bail', AllocationRules::unassigned()],
            'secondary_allocations_ids.*' => ['exclude_with:deploy', 'integer', AllocationRules::unassigned()],

            'deploy' => ['sometimes', 'required', 'array'],
            'deploy.locations' => ['present_with:deploy', 'array', 'list'],
            'deploy.locations.*' => ['required', Rule::anyOf([['integer:strict'], ['string']]), 'integer', 'min:1'],
            'deploy.dedicated_ip' => ['required_with:deploy', 'boolean'],
            'deploy.port_range' => ['present_with:deploy', 'array', 'list'],
            'deploy.port_range.*' => ['string'],
            // Deploy tags (D): the reservations this deployment opts into. Each may
            // be given as a tag id or a slug; existence is checked in after()
            // so the message can name the offending value.
            'deploy.tags' => ['array'],
            'deploy.tags.*' => ['string'],

            'start_on_completion' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Normalize the validated data for the server creation service.
     *
     * @return ServerCreationAttributes
     */
    public function payload(): array
    {
        $data = parent::validated();

        $payload = [
            'external_id' => Arr::get($data, 'external_id'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'owner_id' => Arr::get($data, 'owner_id'),
            'egg_id' => Arr::get($data, 'egg_id'),
            'image' => Arr::get($data, 'docker_image'),
            'startup' => Arr::get($data, 'startup'),
            'environment' => Arr::get($data, 'environment'),
            'memory' => Arr::get($data, 'memory'),
            'swap' => Arr::get($data, 'swap'),
            'disk' => Arr::get($data, 'disk'),
            'io' => Arr::get($data, 'io'),
            'cpu' => Arr::get($data, 'cpu'),
            'threads' => Arr::get($data, 'threads'),
            'skip_scripts' => Arr::get($data, 'skip_scripts', false),
            'allocation_id' => Arr::get($data, 'primary_allocation_id'),
            'allocation_additional' => Arr::get($data, 'secondary_allocations_ids'),
            'start_on_completion' => Arr::get($data, 'start_on_completion', false),
            'database_limit' => Arr::get($data, 'database_limit'),
            'allocation_limit' => Arr::get($data, 'allocation_limit'),
            'backup_limit' => Arr::get($data, 'backup_limit'),
            'oom_disabled' => Arr::get($data, 'oom_disabled'),
            'extensions' => $this->extensionValues(),
        ];
        JsonValueGuard::assertPayload9($payload);

        return ServerCreationData::parse($payload);
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [$this->validateDeployTagsExist(...)];
    }

    /** Build a deployment object from the deploy payload, or null when absent. */
    public function getDeploymentObject(): ?DeploymentObject
    {
        if (($this->input('deploy')) === null) {
            return null;
        }

        $object = new DeploymentObject;
        $object->setDedicated($this->boolean('deploy.dedicated_ip'));
        $object->setLocations(JsonValueGuard::normalizedIntegerList(JsonValueGuard::integerStringList($this->input('deploy.locations', []))));
        $object->setPorts(JsonValueGuard::stringList($this->input('deploy.port_range', [])));
        $object->setTags($this->resolveDeployTagSlugs());

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return Server::class;
    }
}
