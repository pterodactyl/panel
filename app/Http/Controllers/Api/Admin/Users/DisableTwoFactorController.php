<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Users;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Users\DisablesTwoFactor;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Users\DisableTwoFactorRequest;
use Pterodactyl\Models\User;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Users', 'Create, update, retrieve, and delete panel users.')]
class DisableTwoFactorController extends AdminApiController
{
    /**
     * Disable user two-factor authentication.
     */
    #[Endpoint('Disable user two-factor authentication', 'Clears the configured TOTP secret and disables two-factor authentication for a user.')]
    #[ScribeResponse(status: 204, description: 'Two-factor authentication disabled.')]
    public function __invoke(DisableTwoFactorRequest $request, DisablesTwoFactor $twoFactor, User $user): Response
    {
        $twoFactor->disable($user);

        Activity::event('admin:user.disable-2fa')
            ->subject($user)
            ->property('email', $user->email)
            ->log();

        return $this->returnNoContent();
    }
}
