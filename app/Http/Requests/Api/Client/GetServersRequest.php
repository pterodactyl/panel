<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\User;

class GetServersRequest extends ClientApiRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the filtering method for servers when the client base endpoint is requested.
     *
     * @return int
     */
    public function getFilterLevel(): int
    {
        switch ($this->input('type')) {
            case 'all':
                return User::FILTER_LEVEL_ALL;
                break;
            case 'admin':
                return User::FILTER_LEVEL_ADMIN;
                break;
            case 'owner':
                return User::FILTER_LEVEL_OWNER;
                break;
            case 'subuser-of':
            default:
                return User::FILTER_LEVEL_SUBUSER;
                break;
        }
    }
}
