<?php

namespace App\Http\Requests\Api\Application\Servers;

use Illuminate\Support\Arr;
use App\Models\Server;
use App\Services\Acl\Api\AdminAcl;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateServerStartupRequest extends ApplicationApiRequest
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
     * Validation rules to run the input against.
     *
     * @return array
     */
    public function rules(): array
    {
        $data = Server::getUpdateRulesForId($this->getModel(Server::class)->id);

        return [
            'startup' => $data['startup'],
            'environment' => 'present|array',
            'egg' => $data['egg_id'],
            'pack' => $data['pack_id'],
            'image' => $data['image'],
            'skip_scripts' => 'present|boolean',
        ];
    }

    /**
     * Return the validated data in a format that is expected by the service.
     *
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        return collect($data)->only(['startup', 'environment', 'skip_scripts'])->merge([
            'egg_id' => Arr::get($data, 'egg'),
            'pack_id' => Arr::get($data, 'pack'),
            'docker_image' => Arr::get($data, 'image'),
        ])->toArray();
    }
}
