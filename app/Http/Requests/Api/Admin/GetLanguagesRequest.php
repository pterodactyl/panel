<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin;

class GetLanguagesRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [];
    }
}
