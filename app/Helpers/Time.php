<?php

namespace Pterodactyl\Helpers;

use Carbon\CarbonImmutable;

final class Time
{
    /**
     * Gets the time offset from the provided timezone relative to UTC as a number. This
     * is used in the database configuration since we can't always rely on there being support
     * for named timezones in MySQL.
     *
     * Returns the timezone as a string like +08:00 or -05:00 depending on the app timezone.
     */
    public static function getMySQLTimezoneOffset(string $timezone): string
    {
        return CarbonImmutable::now($timezone)->getTimezone()->toOffsetName();
    }
}
