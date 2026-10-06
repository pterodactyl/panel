<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Pterodactyl\Enum\Permissions;

class UpdateSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permissions::UserUpdate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ];
    }

    /**
     * @return list<string>
     *
     * @throws BindingResolutionException
     */
    public function permissions(): array
    {
        $requested = parent::permissions();
        if ($this->requesterHasFullAccess()) {
            return $requested;
        }

        $held = $this->requesterPermissions();
        $current = $this->subuser()->permissions;

        return array_values(array_unique(array_merge(
            array_intersect($requested, $held),
            array_diff($current, $held),
            [Permissions::WebsocketConnect->value],
        )));
    }
}
