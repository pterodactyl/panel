<?php

namespace App\Http\Requests\Api\Application\Servers;

use App\Models\Server;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Services\Acl\Api\AdminAcl;
use App\Models\Objects\DeploymentObject;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreServerRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Rules to be applied to this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = Server::getCreateRules();

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'description' => array_merge(['nullable'], $rules['description']),
            'user' => $rules['owner_id'],
            'egg' => $rules['egg_id'],
            'pack' => $rules['pack_id'],
            'docker_image' => $rules['image'],
            'startup' => $rules['startup'],
            'environment' => 'present|array',
            'skip_scripts' => 'sometimes|boolean',
            'oom_disabled' => 'sometimes|boolean',

            // Resource limitations
            'limits' => 'required|array',
            'limits.memory' => $rules['memory'],
            'limits.swap' => $rules['swap'],
            'limits.disk' => $rules['disk'],
            'limits.io' => $rules['io'],
            'limits.cpu' => $rules['cpu'],

            // Application Resource Limits
            'feature_limits' => 'required|array',
            'feature_limits.databases' => $rules['database_limit'],
            'feature_limits.allocations' => $rules['allocation_limit'],

            // Placeholders for rules added in withValidator() function.
            'allocation.default' => '',
            'allocation.additional.*' => '',

            // Automatic deployment rules
            'deploy' => 'sometimes|required|array',
            'deploy.locations' => 'array',
            'deploy.locations.*' => 'integer|min:1',
            'deploy.dedicated_ip' => 'required_with:deploy,boolean',
            'deploy.port_range' => 'array',
            'deploy.port_range.*' => 'string',

            'start_on_completion' => 'sometimes|boolean',
        ];
    }

    /**
     * Normalize the data into a format that can be consumed by the service.
     *
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        return [
            'external_id' => Arr::get($data, 'external_id'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'owner_id' => Arr::get($data, 'user'),
            'egg_id' => Arr::get($data, 'egg'),
            'pack_id' => Arr::get($data, 'pack'),
            'image' => Arr::get($data, 'docker_image'),
            'startup' => Arr::get($data, 'startup'),
            'environment' => Arr::get($data, 'environment'),
            'memory' => Arr::get($data, 'limits.memory'),
            'swap' => Arr::get($data, 'limits.swap'),
            'disk' => Arr::get($data, 'limits.disk'),
            'io' => Arr::get($data, 'limits.io'),
            'cpu' => Arr::get($data, 'limits.cpu'),
            'skip_scripts' => Arr::get($data, 'skip_scripts', false),
            'allocation_id' => Arr::get($data, 'allocation.default'),
            'allocation_additional' => Arr::get($data, 'allocation.additional'),
            'start_on_completion' => Arr::get($data, 'start_on_completion', false),
            'database_limit' => Arr::get($data, 'feature_limits.databases'),
            'allocation_limit' => Arr::get($data, 'feature_limits.allocations'),
        ];
    }

    /*
     * Run validation after the rules above have been applied.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    public function withValidator(Validator $validator)
    {
        $validator->sometimes('allocation.default', [
            'required', 'integer', 'bail',
            Rule::exists('allocations', 'id')->where(function ($query) {
                $query->whereNull('server_id');
            }),
        ], function ($input) {
            return ! ($input->deploy);
        });

        $validator->sometimes('allocation.additional.*', [
            'integer',
            Rule::exists('allocations', 'id')->where(function ($query) {
                $query->whereNull('server_id');
            }),
        ], function ($input) {
            return ! ($input->deploy);
        });

        $validator->sometimes('deploy.locations', 'present', function ($input) {
            return $input->deploy;
        });

        $validator->sometimes('deploy.port_range', 'present', function ($input) {
            return $input->deploy;
        });
    }

    /**
     * Return a deployment object that can be passed to the server creation service.
     *
     * @return \App\Models\Objects\DeploymentObject|null
     */
    public function getDeploymentObject()
    {
        if (is_null($this->input('deploy'))) {
            return null;
        }

        $object = new DeploymentObject;
        $object->setDedicated($this->input('deploy.dedicated_ip', false));
        $object->setLocations($this->input('deploy.locations', []));
        $object->setPorts($this->input('deploy.port_range', []));

        return $object;
    }
}
