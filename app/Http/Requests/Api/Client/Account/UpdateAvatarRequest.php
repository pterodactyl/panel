<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class UpdateAvatarRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return ['avatar' => ['required', 'file', 'max:10240']];
    }
}
