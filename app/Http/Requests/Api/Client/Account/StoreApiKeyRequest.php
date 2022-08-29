<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use IPTools\Range;
use Pterodactyl\Models\ApiKey;
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
        ];
    }

    /**
     * Check that each of the values entered is actually valid.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!is_array($ips = $this->input('allowed_ips'))) {
                return;
            }

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
        });
    }
}
