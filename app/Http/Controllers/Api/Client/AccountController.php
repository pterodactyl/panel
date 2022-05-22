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
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateUsernameRequest;

class AccountController extends ClientApiController
{
    private UserUpdateService $updateService;
    private SessionGuard $sessionGuard;
    private AccountLog $log;

    /**
     * AccountController constructor.
     */
    public function __construct(AuthManager $sessionGuard, UserUpdateService $updateService, AccountLog $log)
    {
        parent::__construct();

        $this->updateService = $updateService;
        $this->sessionGuard = $sessionGuard;
        $this->log = $log;
    }

    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), $request->validated());

        $this->log->create([
            'user_id' => $request->user()->id,
            'action' => 'Email has been updated successfully.',
            'ip_address' => $request->getClientIp(),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @throws \Throwable
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->updateService->handle($request->user(), $request->validated());

        // If you do not update the user in the session you'll end up working with a
        // cached copy of the user that does not include the updated password. Do this
        // to correctly store the new user details in the guard and allow the logout
        // other devices functionality to work.
        $this->sessionGuard->setUser($user);

        $this->sessionGuard->logoutOtherDevices($request->input('password'));

        $this->log->create([
            'user_id' => $request->user()->id,
            'action' => 'Password has been updated successfully.',
            'ip_address' => $request->getClientIp(),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's username.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateUsername(UpdateUsernameRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), $request->validated());

        $this->log->create([
            'user_id' => $request->user()->id,
            'action' => 'Username has been updated successfully.',
            'ip_address' => $request->getClientIp(),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
