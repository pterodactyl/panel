<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;

interface SetsUpTwoFactor
{
    /**
     * Generate a 2FA token and store it in the database before returning the
     * QR code URL. This URL will need to be attached to a QR generating service in
     * order to function.
     *
     * @return array{image_url_data: string, secret: string}
     */
    public function setup(User $user): array;
}
