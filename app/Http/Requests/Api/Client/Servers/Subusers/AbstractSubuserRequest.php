<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractSubuserRequest extends ClientApiRequest
{
    /**
     * @var \Pterodactyl\Models\Subuser|null
     */
    protected $model;

    /**
     * Authorize the request and ensure that a user is not trying to modify themselves.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        if ($this->subuser()->user_id === $this->user()->id) {
            return false;
        }

        return true;
    }

    /**
     * Return the subuser model for the given request which can then be validated. If
     * required request parameters are missing a 404 error will be returned, otherwise
     * a model exception will be returned if the model is not found.
     *
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function subuser()
    {
        /** @var \Pterodactyl\Repositories\Eloquent\SubuserRepository $repository */
        $repository = $this->container->make(SubuserRepository::class);

        $parameters = $this->route()->parameters();
        if (
            ! isset($parameters['server'], $parameters['server'])
            || ! is_string($parameters['subuser'])
            || ! $parameters['server'] instanceof Server
        ) {
            throw new NotFoundHttpException;
        }

        return $this->model ?: $this->model = $repository->getUserForServer(
            $parameters['server']->id, $parameters['subuser']
        );
    }
}
