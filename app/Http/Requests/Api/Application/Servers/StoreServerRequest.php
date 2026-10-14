<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Servers\CreateServer;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Http\Requests\Concerns\FiltersDeployTags;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class StoreServerRequest extends ApplicationApiRequest
{
    use FiltersDeployTags;
    use ValidatesExtensionFields {
        actionInput as private inputWithExtensions;
    }

    protected ?string $resource = AdminAcl::RESOURCE_SERVERS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Rules to be applied to this request. The Application API keeps its own field names and
     * rules; actionInput() hands the values to the action under its names.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = CreateServer::rules();

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'description' => $rules['description'],
            'user' => $rules['owner_id'],
            'egg' => ['required', 'exists:eggs,id'],
            'docker_image' => $rules['image'],
            'startup' => $rules['startup'],
            'environment' => ['present', 'array'],
            'skip_scripts' => $rules['skip_scripts'],
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
            'feature_limits.databases' => ['present', 'nullable', 'integer', 'min:0'],
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => ['present', 'nullable', 'integer', 'min:0'],

            // Allocations are chosen by hand unless a deployment object picks them.
            'allocation.default' => ['exclude_with:deploy', ...$rules['allocation_id']],
            'allocation.additional.*' => ['exclude_with:deploy', ...$rules['allocation_additional.*']],

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

            'start_on_completion' => $rules['start_on_completion'],
        ];
    }

    /**
     * The validated input under the action's field names: user and egg are its owner_id and
     * egg_id, and docker_image its image. The limits, the feature limits and the default and
     * additional allocations move to its top level, read with Laravel's typed accessors. The
     * deployment goes to the action as getDeploymentObject().
     *
     * @return array<string, mixed>
     */
    public function actionInput(): array
    {
        $input = $this->inputWithExtensions();
        $validated = $this->safe();

        $input['owner_id'] = $input['user'] ?? null;
        $input['egg_id'] = $input['egg'] ?? null;
        $input['image'] = $input['docker_image'] ?? null;
        foreach (['memory', 'swap', 'disk', 'io', 'cpu'] as $limit) {
            $input[$limit] = $validated->integer('limits.'.$limit);
        }

        $input['threads'] = $validated->filled('limits.threads') ? $validated->string('limits.threads')->toString() : null;
        $input['database_limit'] = $validated->filled('feature_limits.databases') ? $validated->integer('feature_limits.databases') : null;
        $input['allocation_limit'] = $validated->filled('feature_limits.allocations') ? $validated->integer('feature_limits.allocations') : null;
        $input['backup_limit'] = $validated->filled('feature_limits.backups') ? $validated->integer('feature_limits.backups') : null;
        $input['allocation_id'] = $validated->filled('allocation.default') ? $validated->integer('allocation.default') : null;
        $input['allocation_additional'] = array_map(fn (int|string $key): int => $validated->integer('allocation.additional.'.$key), array_keys($validated->array('allocation.additional')));
        unset($input['user'], $input['egg'], $input['docker_image'], $input['limits'], $input['feature_limits'], $input['allocation'], $input['deploy']);

        return $input;
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
        $object->setLocations(array_map(fn (int|string $index): int => $this->integer("deploy.locations.{$index}"), array_keys($this->array('deploy.locations'))));
        $object->setPorts(array_map(fn (int|string $index): string => $this->string("deploy.port_range.{$index}")->toString(), array_keys($this->array('deploy.port_range'))));
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

    private function validateDeployTagReference(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail("The {$attribute} field must be a tag ID or slug.");
        }
    }
}
