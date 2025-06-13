<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Events\Failed;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Pterodactyl\Services\Users\UserCreationService;

abstract class AbstractRegisterController extends Controller
{
    use AuthenticatesUsers;

    protected AuthManager $auth;

    /**
     * Lockout time for failed register requests.
     */
    protected int $lockoutTime;

    /**
     * After how many attempts should registers be throttled and locked.
     */
    protected int $maxRegisterAttempts;

    /**
     * RegisterController constructor.
     */
    public function __construct()
    {
        $this->lockoutTime = config('auth.lockout.time');
        $this->maxRegisterAttempts = config('auth.lockout.attempts');
        $this->auth = Container::getInstance()->make(AuthManager::class);
    }

    /**
     * Get the failed register response instance.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    protected function sendFailedRegisterResponse(Request $request, Authenticatable $user = null, string $message = null)
    {
        $this->incrementLoginAttempts($request);
        $this->fireFailedRegisterEvent($user, [
            $request->input('email'),
            $request->input('username'),
        ]);

        throw new DisplayException(trans('auth.failed'));
    }

    /**
     * Send the response after the user was authenticated.
     */
    protected function sendRegisterResponse(Request $request): JsonResponse
    {
        $this->clearLoginAttempts($request);

        $connection = app(\Illuminate\Database\ConnectionInterface::class);
        $hasher = app(\Illuminate\Contracts\Hashing\Hasher::class);
        $passwordBroker = app(\Illuminate\Contracts\Auth\PasswordBroker::class);
        $repository = app(\Pterodactyl\Contracts\Repository\UserRepositoryInterface::class);

        $user = new UserCreationService($connection, $hasher, $passwordBroker, $repository);

        $user->handle([
            'email' => $request->input('email'),
            'username' => $request->input('username'),
            'name_first' => $request->input('firstname'),
            'name_last' => $request->input('lastname')
        ]);

        return new JsonResponse([
            'data' => [
                'complete' => true,
                'intended' => $this->redirectPath()
            ],
        ]);
    }

    /**
     * Fire a failed register event.
     */
    protected function fireFailedRegisterEvent(Authenticatable $user = null, array $credentials = [])
    {
        Event::dispatch(new Failed('auth', $user, $credentials));
    }
}