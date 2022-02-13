<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;

class AccountController extends ClientApiController
{
    private UserUpdateService $updateService;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $sessionGuard;

    /**
     * AccountController constructor.
     */
    public function __construct(UserUpdateService $updateService, AuthManager $sessionGuard)
    {
        parent::__construct();

        $this->updateService = $updateService;
        $this->sessionGuard = $sessionGuard;
    }

    /**
     * Gets information about the currently authenticated user.
     */
    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith(AccountTransformer::class)
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
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
        if (method_exists($this->sessionGuard, 'setUser')) {
            $this->sessionGuard->setUser($user);
        }

        // TODO: Find another way to do this, function doesn't exist due to API changes.
        //$this->sessionGuard->logoutOtherDevices($request->input('password'));

        return $this->returnNoContent();
    }
}
