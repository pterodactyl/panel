<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\User;

interface DeletesUsers
{
    /**
     * A user who owns servers is not deleted.
     *
     * @throws DisplayException
     */
    public function delete(User $user): ?bool;
}
