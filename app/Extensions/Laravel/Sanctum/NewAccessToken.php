<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Laravel\Sanctum;

use Pterodactyl\Models\ApiKey;

/**
 * Panel equivalent of Sanctum's NewAccessToken value object. It is intentionally
 * not a subclass: Sanctum natively types its token property as
 * PersonalAccessToken, which the panel's ApiKey model does not extend.
 */
class NewAccessToken
{
    public function __construct(
        public ApiKey $accessToken,
        public string $plainTextToken,
    ) {}
}
