<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Closure;
use Exception;
use Illuminate\Validation\Validator;
use IPTools\Range;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Validation\ApiKeyRules;

class StoreApiKeyRequest extends ClientApiRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ApiKeyRules::rules();

        return [
            'description' => $rules['memo'],
            'allowed_ips' => [...$rules['allowed_ips'], 'list', 'max:50'],
            'allowed_ips.*' => ['string'],
        ];
    }

    /**
     * Check that each of the values entered is actually valid.
     */
    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! is_array($ips = $this->input('allowed_ips'))) {
                return;
            }

            foreach ($ips as $index => $ip) {
                $valid = false;
                if (! is_string($ip)) {
                    $validator->errors()->add("allowed_ips.{$index}", 'The IP address or CIDR range must be a string.');

                    continue;
                }

                try {
                    $valid = Range::parse($ip)->valid();
                } catch (Exception $exception) {
                    throw_if($exception->getMessage() !== 'Invalid IP address format', $exception);
                } finally {
                    $validator->errors()->addIf(! $valid, "allowed_ips.{$index}", '"'.$ip.'" is not a valid IP address or CIDR range.');
                }
            }
        }];
    }
}
