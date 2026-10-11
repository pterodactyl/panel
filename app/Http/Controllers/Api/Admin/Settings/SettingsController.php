<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Fluent;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\GetSettingsRequest;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Settings', 'View and update global panel settings.')]
class SettingsController extends AdminApiController
{
    private const array SETTINGS_EXAMPLE = [
        'general' => [
            'app:name' => 'Pterodactyl',
            'pterodactyl:auth:2fa_required' => 0,
            'app:locale' => 'en',
        ],
        'mail' => [
            'mail:default' => 'smtp',
            'mail:mailers:smtp:host' => 'mail.example.com',
            'mail:mailers:smtp:port' => 587,
            'mail:mailers:smtp:encryption' => 'tls',
            'mail:mailers:smtp:username' => 'panel@example.com',
            'mail:mailers:smtp:password' => '',
            'mail:from:address' => 'panel@example.com',
            'mail:from:name' => 'Pterodactyl',
        ],
        'advanced' => [
            'recaptcha:enabled' => false,
            'recaptcha:secret_key' => '',
            'recaptcha:website_key' => 'website',
            'pterodactyl:guzzle:timeout' => 15,
            'pterodactyl:guzzle:connect_timeout' => 5,
            'pterodactyl:client_features:allocations:enabled' => false,
            'pterodactyl:client_features:allocations:range_start' => null,
            'pterodactyl:client_features:allocations:range_end' => null,
        ],
        'meta' => [
            'load_environment_only' => false,
            'show_recaptcha_warning' => false,
        ],
    ];

    /**
     * Return current panel settings.
     */
    #[Endpoint('Get settings', 'Returns current general, mail, advanced, and metadata settings.')]
    #[ScribeResponse(self::SETTINGS_EXAMPLE, description: 'Settings returned.')]
    public function index(GetSettingsRequest $request): JsonResponse
    {
        // Settings saved from the panel come back from the database as strings, so numbers are cast on read.
        $settings = Fluent::make(config()->all());

        return new JsonResponse([
            'general' => [
                'app:name' => config('app.name'),
                'pterodactyl:auth:2fa_required' => $settings->integer('pterodactyl.auth.2fa_required'),
                'app:locale' => config('app.locale'),
            ],
            'mail' => [
                'mail:default' => config('mail.default'),
                'mail:mailers:smtp:host' => config('mail.mailers.smtp.host'),
                'mail:mailers:smtp:port' => $settings->integer('mail.mailers.smtp.port'),
                'mail:mailers:smtp:encryption' => config('mail.mailers.smtp.encryption'),
                'mail:mailers:smtp:username' => config('mail.mailers.smtp.username'),
                // Never return the SMTP password to the client.
                'mail:mailers:smtp:password' => '',
                'mail:from:address' => config('mail.from.address'),
                'mail:from:name' => config('mail.from.name'),
            ],
            'advanced' => [
                'recaptcha:enabled' => config()->boolean('recaptcha.enabled'),
                'recaptcha:secret_key' => '',
                'recaptcha:website_key' => config('recaptcha.website_key'),
                'pterodactyl:guzzle:timeout' => $settings->integer('pterodactyl.guzzle.timeout'),
                'pterodactyl:guzzle:connect_timeout' => $settings->integer('pterodactyl.guzzle.connect_timeout'),
                'pterodactyl:client_features:allocations:enabled' => config()->boolean('pterodactyl.client_features.allocations.enabled'),
                'pterodactyl:client_features:allocations:range_start' => config('pterodactyl.client_features.allocations.range_start'),
                'pterodactyl:client_features:allocations:range_end' => config('pterodactyl.client_features.allocations.range_end'),
            ],
            'meta' => [
                'load_environment_only' => config()->boolean('pterodactyl.load_environment_only', false),
                'show_recaptcha_warning' => config('recaptcha._shipped_secret_key') === config('recaptcha.secret_key')
                    || config('recaptcha._shipped_website_key') === config('recaptcha.website_key'),
            ],
        ]);
    }
}
