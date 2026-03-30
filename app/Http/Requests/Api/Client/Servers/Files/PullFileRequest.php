<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class PullFileRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', function ($attribute, $value, $fail) {
                $this->validateUrlIsNotInternal($attribute, $value, $fail);
            }],
            'directory' => 'nullable|string',
            'filename' => 'nullable|string',
            'use_header' => 'boolean',
            'foreground' => 'boolean',
        ];
    }

    /**
     * Ensures the provided URL does not resolve to an internal or reserved IP address
     * to prevent Server-Side Request Forgery (SSRF) attacks. Both the declared host
     * and its resolved IPs (to prevent DNS rebinding) are checked.
     */
    protected function validateUrlIsNotInternal(string $attribute, string $value, callable $fail): void
    {
        $host = parse_url($value, PHP_URL_HOST);

        if (empty($host)) {
            $fail('Unable to parse the host from the provided URL.');
            return;
        }

        // Strip IPv6 brackets so filter_var can validate correctly.
        $bare = ltrim(rtrim($host, ']'), '[');

        $ips = [];

        if (filter_var($bare, FILTER_VALIDATE_IP)) {
            // Host is already an IP literal — validate it directly.
            $ips[] = $bare;
        } else {
            // Resolve the hostname to catch DNS-based redirects to internal ranges.
            $resolved = @gethostbynamel($host);
            if ($resolved === false || empty($resolved)) {
                $fail('The URL hostname could not be resolved to a valid IP address.');
                return;
            }
            $ips = $resolved;
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
                $fail('The URL must not resolve to an internal, loopback, or reserved IP address.');
                return;
            }
        }
    }
}
