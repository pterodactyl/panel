<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Pterodactyl\Data\ServerCreationData;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Http\Requests\Concerns\FiltersDeployTags;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\AllocationRules;
use Pterodactyl\Validation\ServerRules;

class StoreServerRequest extends ApplicationApiRequest
{
    use FiltersDeployTags;

    protected ?string $resource = AdminAcl::RESOURCE_SERVERS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Rules to be applied to this request.
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
            'user' => $rules['owner_id'],
            'egg' => $rules['egg_id'],
            'docker_image' => $rules['image'],
            'startup' => $rules['startup'],
            'environment' => ['present', 'array'],
            'skip_scripts' => ['sometimes', 'boolean'],
            'oom_disabled' => ['sometimes', 'boolean'],

            // Resource limitations
            'limits' => ['required', 'array'],
            'limits.memory' => $rules['memory'],
            'limits.swap' => $rules['swap'],
            'limits.disk' => $rules['disk'],
            'limits.io' => $rules['io'],
            'limits.threads' => $rules['threads'],
            'limits.cpu' => $rules['cpu'],

            // Application Resource Limits
            'feature_limits' => ['required', 'array'],
            'feature_limits.databases' => $rules['database_limit'],
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => $rules['backup_limit'],

            // Allocations are chosen by hand unless a deployment object picks them.
            'allocation.default' => ['exclude_with:deploy', 'required', 'integer', 'bail', AllocationRules::unassigned()],
            'allocation.additional.*' => ['exclude_with:deploy', 'integer', AllocationRules::unassigned()],

            // Automatic deployment rules
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
            'deploy.tags.*' => [$this->validateDeployTagReference(...)],

            'start_on_completion' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Normalize the data into a format that can be consumed by the service.
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
            'owner_id' => Arr::get($data, 'user'),
            'egg_id' => Arr::get($data, 'egg'),
            'image' => Arr::get($data, 'docker_image'),
            'startup' => Arr::get($data, 'startup'),
            'environment' => Arr::get($data, 'environment'),
            'memory' => Arr::get($data, 'limits.memory'),
            'swap' => Arr::get($data, 'limits.swap'),
            'disk' => Arr::get($data, 'limits.disk'),
            'io' => Arr::get($data, 'limits.io'),
            'cpu' => Arr::get($data, 'limits.cpu'),
            'threads' => Arr::get($data, 'limits.threads'),
            'skip_scripts' => Arr::get($data, 'skip_scripts', false),
            'allocation_id' => Arr::get($data, 'allocation.default'),
            'allocation_additional' => Arr::get($data, 'allocation.additional'),
            'start_on_completion' => Arr::get($data, 'start_on_completion', false),
            'database_limit' => Arr::get($data, 'feature_limits.databases'),
            'allocation_limit' => Arr::get($data, 'feature_limits.allocations'),
            'backup_limit' => Arr::get($data, 'feature_limits.backups'),
            'oom_disabled' => Arr::get($data, 'oom_disabled'),
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

    /**
     * Return a deployment object that can be passed to the server creation service.
     */
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

    /** @phpstan-assert int|string $value */
    private function validateDeployTagReference(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail("The {$attribute} field must be a tag ID or slug.");
        }
    }
}
