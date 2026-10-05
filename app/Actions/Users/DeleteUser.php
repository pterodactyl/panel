<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Pterodactyl\Contracts\Users\DeletesUsers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\User;

final class DeleteUser implements DeletesUsers
{
    /**
     * Delete a user from the panel only if they have no servers attached to their account.
     *
     * @throws DisplayException
     */
    public function delete(User $user): ?bool
    {
        if ($user->servers()->exists()) {
            throw new DisplayException(trans('admin/user.exceptions.user_has_servers'));
        }

        return $user->delete();
    }
}
