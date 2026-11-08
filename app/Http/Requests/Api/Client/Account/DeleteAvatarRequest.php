<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

/**
 * Users may always remove their own avatar, even after the panel stops accepting uploads.
 */
class DeleteAvatarRequest extends ClientApiRequest {}
