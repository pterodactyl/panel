<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Api\CreateAccountApiKey;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesAllowedIps;

class StoreApiKeyRequest extends ClientApiRequest
{
    use ValidatesAllowedIps;

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateAccountApiKey::rules();
    }

    /**
     * Check that each of the values entered is actually valid.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [self::validateAllowedIps(...)];
    }

    /**
     * The validated IP addresses and CIDR ranges the key may be used from, or null when
     * none were sent.
     *
     * @return list<string>|null
     */
    public function allowedIps(): ?array
    {
        $validated = $this->safe();
        if (! $validated->filled('allowed_ips')) {
            return null;
        }

        return array_map(fn (int|string $key): string => $validated->string('allowed_ips.'.$key)->toString(), array_keys($validated->array('allowed_ips')));
    }
}
