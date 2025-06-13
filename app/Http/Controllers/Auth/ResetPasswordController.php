<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Pterodactyl\Http\Requests\Auth\ResetPasswordRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * The URL to redirect users to after password reset.
     */
    public string $redirectTo = '/';

    protected bool $hasTwoFactor = false;

    /**
     * ResetPasswordController constructor.
     */
    public function __construct(
        private Dispatcher $dispatcher,
        private Hasher $hasher,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Reset the given user's password.
     *
     * @throws DisplayException
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($response === Password::PASSWORD_RESET) {
            return $this->sendResetResponse();
        }

        throw new DisplayException(trans($response));
    }

    /**
     * Reset the given user's password. If the user has two-factor authentication enabled on their
     * account do not automatically log them in. In those cases, send the user back to the login
     * form with a note telling them their password was changed and to log back in.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword|\Pterodactyl\Models\User $user
     * @param string $password
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    protected function resetPassword($user, $password)
    {
        $user = $this->userRepository->update($user->id, [
            'password' => $this->hasher->make($password),
            $user->getRememberTokenName() => Str::random(60),
        ]);

        $this->dispatcher->dispatch(new PasswordReset($user));

        // If the user is not using 2FA log them in, otherwise skip this step and force a
        // fresh login where they'll be prompted to enter a token.
        if (!$user->use_totp) {
            $this->guard()->login($user);
        }

        $this->hasTwoFactor = $user->use_totp;
    }

    /**
     * Send a successful password reset response back to the callee.
     */
    protected function sendResetResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'redirect_to' => $this->redirectTo,
            'send_to_login' => $this->hasTwoFactor,
        ]);
    }
}
