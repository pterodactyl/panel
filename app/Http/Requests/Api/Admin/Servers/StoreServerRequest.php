<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Servers\CreateServer;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\FiltersDeployTags;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Models\Server;

class StoreServerRequest extends AdminApiRequest
{
    use FiltersDeployTags;
    use ValidatesExtensionFields {
        actionInput as private inputWithExtensions;
    }

    public function permissions(): array
    {
        return [Permissions::AdminServersCreate];
    }

    /**
     * Validation rules for creating a server, under the admin API's field names. The fields
     * it checks differently from the action keep their own rules: it requires the environment
     * and the database and backup limits, refuses a null OOM flag, and checks only that the
     * egg exists, so a missing egg reports one error.
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
            'owner_id' => $rules['owner_id'],
            'egg_id' => ['required', 'exists:eggs,id'],
            'docker_image' => $rules['image'],
            'startup' => $rules['startup'],
            'environment' => ['present', 'array'],
            'skip_scripts' => $rules['skip_scripts'],
            'oom_disabled' => ['sometimes', 'boolean'],

            'memory' => $rules['memory'],
            'swap' => $rules['swap'],
            'disk' => $rules['disk'],
            'io' => $rules['io'],
            'threads' => $rules['threads'],
            'cpu' => $rules['cpu'],

            'database_limit' => ['present', 'nullable', 'integer', 'min:0'],
            'allocation_limit' => $rules['allocation_limit'],
            'backup_limit' => ['present', 'nullable', 'integer', 'min:0'],

            // Allocations are chosen by hand unless a deployment object picks them.
            'primary_allocation_id' => ['exclude_with:deploy', ...$rules['allocation_id']],
            'secondary_allocations_ids.*' => ['exclude_with:deploy', ...$rules['allocation_additional.*']],

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

            'start_on_completion' => $rules['start_on_completion'],
        ];
    }

    /**
     * The validated input under the action's field names: docker_image is its image, and
     * primary_allocation_id and secondary_allocations_ids its allocation_id and
     * allocation_additional. The deployment goes to the action as getDeploymentObject().
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        $input = $this->inputWithExtensions();
        $input['image'] = $input['docker_image'] ?? null;
        $input['allocation_id'] = $input['primary_allocation_id'] ?? null;
        $input['allocation_additional'] = $input['secondary_allocations_ids'] ?? null;
        unset($input['docker_image'], $input['primary_allocation_id'], $input['secondary_allocations_ids'], $input['deploy']);

        return $input;
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
}
