<?php

$proxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    /*
     * SettingsServiceProvider overrides this value when the database
     * contains settings::trustedproxy:proxies.
     *
     * Keep the environment value as a fallback for existing installations.
     */
    'proxies' => in_array($proxies, ['*', '**'], true)
        ? $proxies
        : array_values(array_filter(
            array_map('trim', explode(',', $proxies)),
            static fn (string $proxy): bool => $proxy !== ''
        )),
];
