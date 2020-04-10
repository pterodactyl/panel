<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class SubuserRequest extends ClientApiRequest
{
    /**
     * @var \Pterodactyl\Models\Subuser|null
     */
    protected $model;

    /**
     * Authorize the request and ensure that a user is not trying to modify themselves.
     *
     * @return bool
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        // If there is a subuser present in the URL, validate that it is not the same as the
        // current request user. You're not allowed to modify yourself.
        if ($this->route()->hasParameter('subuser')) {
            if ($this->endpointSubuser()->user_id === $this->user()->id) {
                return false;
            }
        }

        // If this is a POST request, validate that the user can even assign the permissions they
        // have selected to assign.
        if ($this->method() === Request::METHOD_POST && $this->has('permissions')) {
            $this->validatePermissionsCanBeAssigned(
                $this->input('permissions') ?? []
            );
        }

        return true;
    }

    /**
     * Validates that the permissions we are trying to assign can actually be assigned
     * by the user making the request.
     *
     * @param array $permissions
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function validatePermissionsCanBeAssigned(array $permissions)
    {
        $user = $this->user();
        /** @var \Pterodactyl\Models\Server $server */
        $server = $this->route()->parameter('server');

        // If we are a root admin or the server owner, no need to perform these checks.
        if ($user->root_admin || $user->id === $server->owner_id) {
            return;
        }

        // Otherwise, get the current subuser's permission set, and ensure that the
        // permissions they are trying to assign are not _more_ than the ones they
        // already have.
        if (count(array_diff($permissions, $this->currentUserPermissions())) > 0) {
            throw new HttpForbiddenException(
                'Cannot assign permissions to a subuser that your account does not actively possess.'
            );
        }
    }

    /**
     * Returns the currently authenticated user's permissions.
     *
     * @return array
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function currentUserPermissions(): array
    {
        /** @var \Pterodactyl\Repositories\Eloquent\SubuserRepository $repository */
        $repository = $this->container->make(SubuserRepository::class);

        /* @var \Pterodactyl\Models\Subuser $model */
        try {
            $model = $repository->findFirstWhere([
                ['server_id', $this->route()->parameter('server')->id],
                ['user_id', $this->user()->id],
            ]);
        } catch (RecordNotFoundException $exception) {
            return [];
        }

        return $model->permissions;
    }

    /**
     * Return the subuser model for the given request which can then be validated. If
     * required request parameters are missing a 404 error will be returned, otherwise
     * a model exception will be returned if the model is not found.
     *
     * This returns the subuser based on the endpoint being hit, not the actual subuser
     * for the account making the request.
     *
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function endpointSubuser()
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
