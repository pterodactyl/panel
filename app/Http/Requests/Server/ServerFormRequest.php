<?php

namespace Pterodactyl\Http\Requests\Server;

use Pterodactyl\Http\Requests\FrontendUserFormRequest;

abstract class ServerFormRequest extends FrontendUserFormRequest
{
    /**
     * Return the user permission to validate this request aganist.
     *
     * @return string
     */
    abstract protected function permission(): string;

    /**
     * Determine if a user has permission to access this resource.
     *
     * @return bool
     */
    public function authorize()
    {
        if (! parent::authorize()) {
            return false;
        }

        return $this->user()->can($this->permission(), $this->attributes->get('server'));
    }
}
