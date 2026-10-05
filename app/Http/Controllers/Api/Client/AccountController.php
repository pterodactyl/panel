<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Users\UpdatesUserEmails;
use Pterodactyl\Contracts\Users\UpdatesUserPasswords;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Client\AccountTransformer;
use Throwable;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Account', 'View and update the authenticated user account.')]
class AccountController extends ClientApiController
{
    private const array INVALID_PASSWORD_ERROR = [
        'errors' => [
            [
                'code' => 'InvalidPasswordProvidedException',
                'status' => '400',
                'detail' => 'The password provided was invalid for this account.',
            ],
        ],
    ];

    private const array THROTTLED_ERROR = [
        'errors' => [
            [
                'code' => 'TooManyRequestsHttpException',
                'status' => '429',
                'detail' => 'Your email address has been changed too many times today. Please try again later.',
            ],
        ],
    ];

    /**
     * @return ApiPayload
     */
    #[Endpoint('Get account', 'Returns profile details for the authenticated user.')]
    #[ResponseFromTransformer(AccountTransformer::class, User::class, resourceKey: 'user')]
    public function index(Request $request): array
    {
        return Fractal::item($request->user())
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toResponseArray();
    }

    /**
     * Update the authenticated user's email address.
     */
    #[Endpoint('Update account email', "Updates the authenticated user's email address after verifying their current password.")]
    #[BodyParam('password', 'string', 'The current account password.', required: true, example: 'current-password')]
    #[ScribeResponse(status: 204, description: 'Email address updated.')]
    #[ScribeResponse(self::INVALID_PASSWORD_ERROR, status: 400, description: 'The current password is invalid.')]
    #[ScribeResponse(self::THROTTLED_ERROR, status: 429, description: 'The account has changed email too many times in the current throttle window.')]
    public function updateEmail(UpdateEmailRequest $request, UpdatesUserEmails $emails): JsonResponse
    {
        $email = JsonValueGuard::string($request->validated('email'));

        $result = $emails->update($request->user(), $email);

        if ($result['changed']) {
            Activity::event('user:account.email-changed')
                ->property(['old' => $result['original'], 'new' => $result['email']])
                ->log();
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @throws Throwable
     */
    #[Endpoint('Update account password', "Updates the authenticated user's password and revokes other active sessions where supported.")]
    #[BodyParam('current_password', 'string', 'The current account password.', required: true, example: 'current-password')]
    #[BodyParam('password_confirmation', 'string', 'Confirmation matching the new password.', required: true, example: 'correct-horse-battery-staple')]
    #[ScribeResponse(status: 204, description: 'Password updated.')]
    #[ScribeResponse(self::INVALID_PASSWORD_ERROR, status: 400, description: 'The current password is invalid.')]
    public function updatePassword(UpdatePasswordRequest $request, UpdatesUserPasswords $passwords): JsonResponse
    {
        $password = JsonValueGuard::string($request->validated('password'));

        $user = $passwords->update($request->user(), $password);

        Activity::event('user:account.password-changed')->subject($user)->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
