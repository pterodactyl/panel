<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class AdvancedSettingsFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'app:debug' => 'required|in:true,false',
            'trustedproxy:proxies' => [
                'nullable',
                'string',
                'max:65535',
                function ($attribute, $value, $fail) {
                    if (in_array($value, ['*', '**'], true)) {
                        return;
                    }

                    foreach (explode(',', $value) as $proxy) {
                        $parts = explode('/', $proxy);
                        $ip = $parts[0];

                        if (
                            count($parts) > 2
                            || filter_var($ip, FILTER_VALIDATE_IP) === false
                        ) {
                            $fail(
                                'Trusted Proxies must contain valid IP addresses or CIDR ranges, or a single * to trust all proxies.'
                            );

                            return;
                        }

                        if (isset($parts[1])) {
                            $maximum = str_contains($ip, ':') ? 128 : 32;

                            if (
                                !ctype_digit($parts[1])
                                || (int) $parts[1] > $maximum
                            ) {
                                $fail('Trusted Proxies contains an invalid CIDR prefix.');

                                return;
                            }
                        }
                    }
                },
            ],
            'recaptcha:enabled' => 'required|in:true,false',
            'recaptcha:secret_key' => 'required|string|max:191',
            'recaptcha:website_key' => 'required|string|max:191',
            'pterodactyl:guzzle:timeout' => 'required|integer|between:1,60',
            'pterodactyl:guzzle:connect_timeout' => 'required|integer|between:1,60',
            'pterodactyl:client_features:allocations:enabled' => 'required|in:true,false',
            'pterodactyl:client_features:allocations:range_start' => [
                'nullable',
                'required_if:pterodactyl:client_features:allocations:enabled,true',
                'integer',
                'between:1024,65535',
            ],
            'pterodactyl:client_features:allocations:range_end' => [
                'nullable',
                'required_if:pterodactyl:client_features:allocations:enabled,true',
                'integer',
                'between:1024,65535',
                'gt:pterodactyl:client_features:allocations:range_start',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('trustedproxy:proxies');

        if ($this->has('trustedproxy:proxies') && is_string($value)) {
            $proxies = preg_split('/[,\r\n]+/', $value);

            $proxies = array_values(array_filter(
                array_map('trim', $proxies),
                static fn (string $proxy): bool => $proxy !== ''
            ));

            $this->merge([
                'trustedproxy:proxies' => implode(',', $proxies),
            ]);
        }
    }

    public function attributes(): array
    {
        return [
            'app:debug' => 'Debug Mode',
            'trustedproxy:proxies' => 'Trusted Proxies',
            'recaptcha:enabled' => 'reCAPTCHA Enabled',
            'recaptcha:secret_key' => 'reCAPTCHA Secret Key',
            'recaptcha:website_key' => 'reCAPTCHA Website Key',
            'pterodactyl:guzzle:timeout' => 'HTTP Request Timeout',
            'pterodactyl:guzzle:connect_timeout' => 'HTTP Connection Timeout',
            'pterodactyl:client_features:allocations:enabled' => 'Auto Create Allocations Enabled',
            'pterodactyl:client_features:allocations:range_start' => 'Starting Port',
            'pterodactyl:client_features:allocations:range_end' => 'Ending Port',
        ];
    }
}
