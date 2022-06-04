<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\AccountLog;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateUsernameRequest;

class AccountController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $manager;

    /**
     * @var \Pterodactyl\Models\AccountLog
     */
    private $log;

    /**
     * AccountController constructor.
     */
    public function __construct(AuthManager $manager, UserUpdateService $updateService, AccountLog $log)
    {
        parent::__construct();

        $this->updateService = $updateService;
        $this->manager = $manager;
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
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $original = $request->user()->email;
        $this->updateService->handle($request->user(), $request->validated());

        $this->log->create([
            'user_id' => $request->user()->id,
            'ip_address' => $request->getClientIp(),
        ]);

        Activity::event('user:account.email-changed')
            ->property(['old' => $original, 'new' => $request->input('email')]);

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

        $guard = $this->manager->guard();
        // If you do not update the user in the session you'll end up working with a
        // cached copy of the user that does not include the updated password. Do this
        // to correctly store the new user details in the guard and allow the logout
        // other devices functionality to work.
        $guard->setUser($user);

        // This method doesn't exist in the stateless Sanctum world.
        if (method_exists($guard, 'logoutOtherDevices')) {
            $guard->logoutOtherDevices($request->input('password'));
        }

        $this->log->create([
            'user_id' => $request->user()->id,
            'action' => 'Password has been updated successfully.',
            'ip_address' => $request->getClientIp(),
        ]);

        Activity::event('user:account.password-changed')->log();

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
    
        Activity::event('user:account.username-changed')->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
