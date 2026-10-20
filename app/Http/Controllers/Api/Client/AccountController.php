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
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdatePasswordRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\UpdateAvatarRequest;
use Pterodactyl\Http\Resources\Api\Client\AccountResource;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Users\UserAvatarService;
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

    #[Endpoint('Get account', 'Returns profile details for the authenticated user.')]
    #[ResponseFromResource(AccountResource::class, User::class, resourceKey: 'user')]
    public function index(Request $request): AccountResource
    {
        return AccountResource::make($request->user());
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
        $emails->update($request->user(), $request->string('email')->toString());

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
        $passwords->update($request->user(), $request->string('password')->toString());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function updateAvatar(UpdateAvatarRequest $request, UserAvatarService $avatars): AccountResource
    {
        $avatars->replace($request->user(), $request->file('avatar'));

        return AccountResource::make($request->user()->refresh());
    }

    public function deleteAvatar(Request $request, UserAvatarService $avatars): JsonResponse
    {
        $avatars->clear($request->user());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
