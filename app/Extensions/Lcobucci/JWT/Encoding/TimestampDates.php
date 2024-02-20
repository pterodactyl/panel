<?php

namespace Pterodactyl\Extensions\Lcobucci\JWT\Encoding;

use Lcobucci\JWT\ClaimsFormatter;
use Lcobucci\JWT\Token\RegisteredClaims;

final class TimestampDates implements ClaimsFormatter
{
    /**
     * The default time encoder for JWTs using this library is not supported correctly
     * by Wings and will cause a flood of errors and panic conditions because the times
     * cannot be parsed correctly. The default is time with microseconds, we just need
     * to use the normal unix timestamp here.
     */
    public function formatClaims(array $claims): array
    {
        foreach (RegisteredClaims::DATE_CLAIMS as $claim) {
            if (!array_key_exists($claim, $claims)) {
                continue;
            }

            assert($claims[$claim] instanceof \DateTimeImmutable);
            $claims[$claim] = $claims[$claim]->getTimestamp();
        }

        return $claims;
    }
}
