<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Pterodactyl\Http\Requests\Auth\ResetPasswordRequest;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * The URL to redirect users to after password reset.
     *
     * @var string
     */
    public $redirectTo = '/';

    /**
     * Reset the given user's password.
     *
     * @param \Pterodactyl\Http\Requests\Auth\ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
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
     * Send a successful password reset response back to the callee.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'redirect_to' => $this->redirectTo,
        ]);
    }
}
