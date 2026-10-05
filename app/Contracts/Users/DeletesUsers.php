<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\User;

interface DeletesUsers
{
    /**
     * Delete a user from the panel only if they have no servers attached to their account.
     *
     * @throws DisplayException
     */
    public function delete(User $user): ?bool;
}
