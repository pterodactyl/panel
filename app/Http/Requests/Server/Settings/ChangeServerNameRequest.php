<?php

namespace Pterodactyl\Http\Requests\Server\Settings;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Server\ServerFormRequest;

class ChangeServerNameRequest extends ServerFormRequest
{
    /**
     * Permission to use when checking if a user can access this resource.
     *
     * @return string
     */
    protected function permission(): string
    {
        return 'edit-name';
    }

    /**
     * Rules to use when validating the submitted data.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => Server::getCreateRules()['name'],
        ];
    }
}
