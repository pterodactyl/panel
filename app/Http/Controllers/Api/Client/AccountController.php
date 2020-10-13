<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;

class AccountController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * @var \Illuminate\Auth\SessionGuard
     */
    private $sessionGuard;

    /**
     * AccountController constructor.
     *
     * @param \Illuminate\Auth\AuthManager $sessionGuard
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(AuthManager $sessionGuard, UserUpdateService $updateService)
    {
        parent::__construct();

        $this->updateService = $updateService;
        $this->sessionGuard = $sessionGuard;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), $request->validated());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), $request->validated());

        $this->sessionGuard->logoutOtherDevices($request->input('password'));

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
