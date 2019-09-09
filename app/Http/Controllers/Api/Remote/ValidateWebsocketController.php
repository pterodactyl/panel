<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Http\Response;
use Illuminate\Contracts\Cache\Repository;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\UserRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Http\Requests\Api\Remote\AuthenticateWebsocketDetailsRequest;

class ValidateWebsocketController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $serverRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\UserRepository
     */
    private $userRepository;

    /**
     * ValidateWebsocketController constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $serverRepository
     * @param \Pterodactyl\Repositories\Eloquent\UserRepository $userRepository
     */
    public function __construct(Repository $cache, ServerRepository $serverRepository, UserRepository $userRepository)
    {
        $this->cache = $cache;
        $this->serverRepository = $serverRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Route allowing the Wings daemon to validate that a websocket route request is
     * valid and that the given user has permission to access the resource.
     *
     * @param \Pterodactyl\Http\Requests\Api\Remote\AuthenticateWebsocketDetailsRequest $request
     * @param string $token
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(AuthenticateWebsocketDetailsRequest $request, string $token)
    {
        $server = $this->serverRepository->getByUuid($request->input('server_uuid'));
        if (! $data = $this->cache->pull('ws:' . $token)) {
            throw new NotFoundHttpException;
        }

        /** @var \Pterodactyl\Models\User $user */
        $user = $this->userRepository->find($data['user_id']);
        if (! $user->can('connect-to-ws', $server)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'You do not have permission to access this resource.');
        }

        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        if (
            $data['server_id'] !== $server->id
            || $node->id !== $server->node_id
            // @todo this doesn't work well in dev currently, need to look into this way more.
            // @todo stems from some issue with the way requests are being proxied.
            // || $data['request_ip'] !== $request->input('originating_request_ip')
        ) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'The token provided is not valid for the requested resource.');
        }

        return Response::create('', Response::HTTP_NO_CONTENT);
    }
}
