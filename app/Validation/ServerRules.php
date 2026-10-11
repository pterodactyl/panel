<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class ServerRules
{
    /**
     * Validation rules for the requests that update a server's startup. Creating a server
     * uses CreateServer::rules(), its build UpdateServerBuild::rules() and its details
     * UpdateServerDetails::rules().
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'egg_id' => ['required', 'exists:eggs,id'],
            'startup' => ['required', 'string'],
            'image' => ['required', 'string', 'max:191', 'regex:/^~?[\w\.\/\-:@ ]*$/'],
        ];
    }
}
