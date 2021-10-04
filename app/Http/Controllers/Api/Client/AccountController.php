<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\SessionGuard;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;

class AccountController extends ClientApiController
{
    private SessionGuard $sessionGuard;
    private UserUpdateService $updateService;

    /**
     * AccountController constructor.
     */
    public function __construct(SessionGuard $sessionGuard, UserUpdateService $updateService)
    {
        parent::__construct();

        $this->sessionGuard = $sessionGuard;
        $this->updateService = $updateService;
    }

    /**
     * Gets information about the currently authenticated user.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith(AccountTransformer::class)
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateEmail(UpdateEmailRequest $request): Response
    {
        $this->updateService->handle($request->user(), $request->validated());

        return $this->returnNoContent();
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @throws \Throwable
     */
    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $user = $this->updateService->handle($request->user(), $request->validated());

        // If you do not update the user in the session you'll end up working with a
        // cached copy of the user that does not include the updated password. Do this
        // to correctly store the new user details in the guard and allow the logout
        // other devices functionality to work.
        $this->sessionGuard->setUser($user);

        $this->sessionGuard->logoutOtherDevices($request->input('password'));

        return $this->returnNoContent();
    }
}
