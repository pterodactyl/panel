<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use IPTools\Range;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Permission;
use Illuminate\Validation\Validator;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreApiKeyRequest extends ClientApiRequest
{
    public function rules(): array
    {
        $rules = ApiKey::getRules();

        return [
            'description' => $rules['memo'],
            'allowed_ips' => [...$rules['allowed_ips'], 'max:50'],
            'allowed_ips.*' => 'string',
            'permissions' => 'sometimes|nullable|array|min:1',
            'permissions.*' => 'string',
            'allowed_servers' => 'sometimes|nullable|array|min:1|max:100',
            'allowed_servers.*' => 'string|uuid',
        ];
    }

    /**
     * Check that each of the values entered is actually valid.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (is_array($ips = $this->input('allowed_ips'))) {
                foreach ($ips as $index => $ip) {
                    $valid = false;
                    try {
                        $valid = Range::parse($ip)->valid();
                    } catch (\Exception $exception) {
                        if ($exception->getMessage() !== 'Invalid IP address format') {
                            throw $exception;
                        }
                    } finally {
                        $validator->errors()->addIf(!$valid, "allowed_ips.{$index}", '"' . $ip . '" is not a valid IP address or CIDR range.');
                    }
                }
            }

            // Ensure that any assigned permissions actually exist on the system.
            if (is_array($permissions = $this->input('permissions'))) {
                $valid = Permission::permissions()
                    ->map(fn ($data, $group) => array_map(fn ($key) => "$group.$key", array_keys($data['keys'])))
                    ->flatten()
                    ->all();

                foreach ($permissions as $index => $permission) {
                    $validator->errors()->addIf(
                        !in_array($permission, $valid, true),
                        "permissions.{$index}",
                        '"' . $permission . '" is not a valid permission.'
                    );
                }
            }

            // Ensure the key is only scoped to servers its owner can already access —
            // an API key must never be a vehicle for accessing anything beyond the
            // user's own reach.
            if (is_array($servers = $this->input('allowed_servers'))) {
                $accessible = $this->user()->accessibleServers()->pluck('uuid')->all();

                foreach ($servers as $index => $uuid) {
                    $validator->errors()->addIf(
                        !in_array($uuid, $accessible, true),
                        "allowed_servers.{$index}",
                        'The specified server could not be found.'
                    );
                }
            }
        });
    }
}
