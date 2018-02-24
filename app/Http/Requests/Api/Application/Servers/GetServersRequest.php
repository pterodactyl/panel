<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

class GetServersRequest extends GetServerRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'search' => 'string|max:100',
        ];
    }
}
