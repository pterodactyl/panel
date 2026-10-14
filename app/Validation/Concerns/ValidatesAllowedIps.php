<?php

declare(strict_types=1);

namespace Pterodactyl\Validation\Concerns;

use Illuminate\Validation\Validator;
use InvalidArgumentException;
use IPTools\Exception\IpException;
use IPTools\Exception\NetworkException;
use IPTools\Range;

trait ValidatesAllowedIps
{
    /**
     * Report each value under `allowed_ips` that is not an IP address or CIDR range. The
     * errors are added after the rules ran, so they follow any the rules reported.
     */
    protected static function validateAllowedIps(Validator $validator): void
    {
        $ips = $validator->getData()['allowed_ips'] ?? null;
        if (! is_array($ips)) {
            return;
        }

        foreach ($ips as $index => $ip) {
            if (! is_string($ip)) {
                $validator->errors()->add("allowed_ips.{$index}", 'The IP address or CIDR range must be a string.');

                continue;
            }

            try {
                $valid = Range::parse($ip)->valid();
            } catch (IpException|NetworkException|InvalidArgumentException) {
                $valid = false;
            }

            if (! $valid) {
                $validator->errors()->add("allowed_ips.{$index}", '"'.$ip.'" is not a valid IP address or CIDR range.');
            }
        }
    }
}
