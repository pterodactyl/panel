<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class Fqdn implements DataAwareRule, ValidationRule
{
    /**
     * @var ValidationData
     */
    protected array $data = [];

    protected ?string $schemeField = null;

    /**
     * Returns a new instance of the rule with a defined scheme set.
     */
    public static function make(?string $schemeField = null): self
    {
        return tap(new self, function (self $fqdn) use ($schemeField): void {
            $fqdn->schemeField = $schemeField;
        });
    }

    /**
     * @param  ValidationData  $data
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validates that the value provided resolves to an IP address. If a scheme is
     * specified when this rule is created additional checks will be applied.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute could not be resolved to a valid IP address.');

            return;
        }

        if (filter_var($value, FILTER_VALIDATE_IP)) {
            // Unless someone owns their IP blocks and pays for a custom certificate, an IP
            // cannot serve HTTPS, so refuse the combination before the node fails to connect.
            if ($this->schemeField && Arr::get($this->data, $this->schemeField) === 'https') {
                $fail('The :attribute must not be an IP address when HTTPS is enabled.');
            }

            return;
        }

        // dns_get_record resolves CNAMEs for us; the suppression is intentional,
        // see https://bugs.php.net/bug.php?id=73149. gethostbyname is the IPv4-only fallback.
        $records = @dns_get_record($value, DNS_A + DNS_AAAA);
        if (! empty($records) || filter_var(gethostbyname($value), FILTER_VALIDATE_IP)) {
            return;
        }

        $fail('The :attribute could not be resolved to a valid IP address.');
    }
}
