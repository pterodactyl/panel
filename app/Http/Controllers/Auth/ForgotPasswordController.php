<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Events\Auth\FailedPasswordReset;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Get the response for a failed password reset link.
     */
    protected function sendResetLinkFailedResponse(Request $request, $response): JsonResponse
    {
        // As noted in #358 we will return success even if it failed
        // to avoid pointing out that an account does or does not
        // exist on the system.
        event(new FailedPasswordReset($request->ip(), $request->input('email')));

        return $this->sendResetLinkResponse($request, Password::RESET_LINK_SENT);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param string $response
     */
    protected function sendResetLinkResponse(Request $request, $response): JsonResponse
    {
        return response()->json([
            'status' => trans($response),
        ]);
    }
}
