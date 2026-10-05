<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Pterodactyl\Events\Auth\FailedPasswordReset;
use Pterodactyl\Http\Controllers\Controller;

#[Group('Authentication', 'Browser session authentication endpoints used by the panel frontend.', authenticated: false)]
class ForgotPasswordController extends Controller
{
    private const array RESET_LINK_SENT_EXAMPLE = [
        'status' => 'We have emailed your password reset link.',
    ];

    private const array CAPTCHA_FAILED_ERROR = [
        'errors' => [
            [
                'code' => 'HttpException',
                'status' => '400',
                'detail' => 'Failed to validate reCAPTCHA data.',
            ],
        ],
    ];

    #[Unauthenticated]
    #[Endpoint('Request password reset email', 'Sends a password reset email when the account exists. The response is intentionally the same when no matching account is found.')]
    #[BodyParam('email', 'string', 'The account email address.', required: true, example: 'admin@example.com')]
    #[BodyParam('g-recaptcha-response', 'string', 'The reCAPTCHA token when reCAPTCHA is enabled.', required: false, example: '03AFcWeA...', nullable: true)]
    #[ScribeResponse(self::RESET_LINK_SENT_EXAMPLE, description: 'Password reset email accepted.')]
    #[ScribeResponse(self::CAPTCHA_FAILED_ERROR, status: 400, description: 'The reCAPTCHA token is invalid or missing.')]
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $response = Password::broker()->sendResetLink($request->only('email'));

        return $response === Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  string  $response
     */
    protected function sendResetLinkFailedResponse(Request $request, $response): JsonResponse // @pest-ignore-type
    {
        // As noted in #358 we will return success even if it failed
        // to avoid pointing out that an account does or does not
        // exist on the system.
        event(new FailedPasswordReset($request->ip() ?? 'unknown', $request->string('email')->toString()));

        return $this->sendResetLinkResponse($request, Password::RESET_LINK_SENT);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  string  $response
     */
    protected function sendResetLinkResponse(Request $request, $response): JsonResponse // @pest-ignore-type
    {
        return response()->json([
            'status' => trans($response),
        ]);
    }
}
