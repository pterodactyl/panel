<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Data\LoginCheckpoint;
use Pterodactyl\Data\LoginResult;
use Pterodactyl\Models\User;

/**
 * The single place that turns "this user has been authenticated" into a panel
 * session. Every method works on the session of the current request, so it
 * must be called from a route that runs the session middleware.
 */
interface CompletesLogins
{
    /**
     * Complete a login for a user whose first factor (a password, or an
     * external identity provider) has been verified. Accounts with two-factor
     * authentication receive a checkpoint that must be confirmed through the
     * login checkpoint endpoint; every other account is signed in immediately.
     */
    public function complete(User $user): LoginResult;

    /**
     * Sign in a user who has passed every factor their account requires,
     * discarding any pending checkpoint. Callers are responsible for having
     * verified the second factor of accounts that use one.
     */
    public function establish(User $user): LoginResult;

    /**
     * The unexpired two-factor checkpoint waiting in the current session, if any.
     */
    public function pendingCheckpoint(): ?LoginCheckpoint;
}
