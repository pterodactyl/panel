<?php

namespace Pterodactyl\Transformers\Api;

use Illuminate\Http\Request;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Exceptions\PterodactylException;

abstract class ApiTransformer extends TransformerAbstract
{
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Setup request object for transformer.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Return the request instance being used for this transformer.
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Determine if an API key from the request has permission to access
     * a resource. This is used when loading includes on the transformed
     * models.
     *
     * @param string $permission
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    protected function authorize(string $permission): bool
    {
        /** @var \Pterodactyl\Models\APIKey $model */
        $model = $this->request->attributes->get('api_key');
        if (! $model->relationLoaded('permissions')) {
            throw new PterodactylException('Permissions must be loaded onto a model before passing to transformer authorize function.');
        }

        $count = $model->getRelation('permissions')->filter(function ($model) use ($permission) {
            return $model->permission === $permission;
        })->count();

        return $count > 0;
    }
}
