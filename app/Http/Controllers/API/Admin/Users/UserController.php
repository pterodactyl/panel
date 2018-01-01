<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Users;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Transformers\Api\Admin\UserTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    private $deletionService;

    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * UserController constructor.
     *
     * @param \Spatie\Fractal\Fractal                                   $fractal
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     * @param \Pterodactyl\Services\Users\UserCreationService           $creationService
     * @param \Pterodactyl\Services\Users\UserDeletionService           $deletionService
     * @param \Pterodactyl\Services\Users\UserUpdateService             $updateService
     */
    public function __construct(
        Fractal $fractal,
        UserRepositoryInterface $repository,
        UserCreationService $creationService,
        UserDeletionService $deletionService,
        UserUpdateService $updateService
    ) {
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Handle request to list all users on the panel. Returns a JSONAPI representation
     * of a collection of users including any defined relations passed in
     * the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $users = $this->repository->all(config('pterodactyl.paginate.api.users'));

        $fractal = $this->fractal->collection($users)
            ->transformWith(new UserTransformer($request))
            ->withResourceName('user')
            ->paginateWith(new IlluminatePaginatorAdapter($users));

        if (config('pterodactyl.api.include_on_list') && $request->filled('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->toArray();
    }

    /**
     * Handle a request to view a single user. Includes any relations that
     * were defined in the request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\User $user
     * @return array
     */
    public function view(Request $request, User $user): array
    {
        $fractal = $this->fractal->item($user)
            ->transformWith(new UserTransformer($request))
            ->withResourceName('user');

        if ($request->filled('include')) {
            $fractal->parseIncludes(explode(',', $request->input('include')));
        }

        return $fractal->toArray();
    }

    /**
     * Update an existing user on the system and return the response. Returns the
     * updated user model response on success. Supports handling of token revocation
     * errors when switching a user from an admin to a normal user.
     *
     * Revocation errors are returned under the 'revocation_errors' key in the response
     * meta. If there are no errors this is an empty array.
     *
     * @param \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @param \Pterodactyl\Models\User                         $user
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UserFormRequest $request, User $user): array
    {
        $this->updateService->setUserLevel(User::USER_LEVEL_ADMIN);
        $collection = $this->updateService->handle($user, $request->normalize());

        $errors = [];
        if (! empty($collection->get('exceptions'))) {
            foreach ($collection->get('exceptions') as $node => $exception) {
                /** @var \GuzzleHttp\Exception\RequestException $exception */
                /** @var \GuzzleHttp\Psr7\Response|null $response */
                $response = method_exists($exception, 'getResponse') ? $exception->getResponse() : null;
                $message = trans('admin/server.exceptions.daemon_exception', [
                    'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
                ]);

                $errors[] = ['message' => $message, 'node' => $node];
            }
        }

        $response = $this->fractal->item($collection->get('model'))
            ->transformWith(new UserTransformer($request))
            ->withResourceName('user');

        if (count($errors) > 0) {
            $response->addMeta([
                'revocation_errors' => $errors,
            ]);
        }

        return $response->toArray();
    }

    /**
     * Store a new user on the system. Returns the created user and a HTTP/201
     * header on successful creation.
     *
     * @param \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(UserFormRequest $request): JsonResponse
    {
        $user = $this->creationService->handle($request->normalize());

        return $this->fractal->item($user)
            ->transformWith(new UserTransformer($request))
            ->withResourceName('user')
            ->addMeta([
                'link' => route('api.admin.user.view', ['user' => $user->id]),
            ])
            ->respond(201);
    }

    /**
     * Handle a request to delete a user from the Panel. Returns a HTTP/204 response
     * on successful deletion.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(User $user): Response
    {
        $this->deletionService->handle($user);

        return response('', 204);
    }
}
