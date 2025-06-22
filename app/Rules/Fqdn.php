<?php

namespace Pterodactyl\Rules;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\DataAwareRule;

class Fqdn implements Rule, DataAwareRule
{
    protected array $data = [];
    protected string $message = '';
    protected ?string $schemeField = null;

    /**
     * @param array $data
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validates that the value provided resolves to an IP address. If a scheme is
     * specified when this rule is created additional checks will be applied.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            // Check if the scheme is set to HTTPS.
            //
            // Unless someone owns their IP blocks and decides to pay who knows how much for a
            // custom SSL cert, IPs will not be able to use HTTPS.  This should prevent most
            // home users from making this mistake and wondering why their node is not working.
            if ($this->schemeField && Arr::get($this->data, $this->schemeField) === 'https') {
                $this->message = 'The :attribute must not be an IP address when HTTPS is enabled.';

                return false;
            }

            return true;
        }

        // Lookup A and AAAA DNS records for the FQDN. Note, this function will also resolve CNAMEs
        // for us automatically, there is no need to manually resolve them here.
        //
        // The error suppression is intentional, see https://bugs.php.net/bug.php?id=73149
        $records = @dns_get_record($value, DNS_A + DNS_AAAA);
        // If no records were returned fall back to trying to resolve the value using the hosts DNS
        // resolution. This will not work for IPv6 which is why we prefer to use `dns_get_record`
        // first.
        if (!empty($records) || filter_var(gethostbyname($value), FILTER_VALIDATE_IP)) {
            return true;
        }

        $this->message = 'The :attribute could not be resolved to a valid IP address.';

        return false;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * Returns a new instance of the rule with a defined scheme set.
     */
    public static function make(?string $schemeField = null): self
    {
        return tap(new static(), function ($fqdn) use ($schemeField) {
            $fqdn->schemeField = $schemeField;
        });
    }
}
