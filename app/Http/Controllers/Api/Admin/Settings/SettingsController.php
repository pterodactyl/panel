<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\GetSettingsRequest;
use Pterodactyl\Support\JsonValueGuard;

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
            'recaptcha:secret_key' => 'secret',
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
    public function index(GetSettingsRequest $request, ConfigRepository $config): JsonResponse
    {
        return new JsonResponse([
            'general' => [
                'app:name' => $config->get('app.name'),
                'pterodactyl:auth:2fa_required' => JsonValueGuard::integer($config->get('pterodactyl.auth.2fa_required')),
                'app:locale' => $config->get('app.locale'),
            ],
            'mail' => [
                'mail:default' => $config->get('mail.default'),
                'mail:mailers:smtp:host' => $config->get('mail.mailers.smtp.host'),
                'mail:mailers:smtp:port' => JsonValueGuard::integer($config->get('mail.mailers.smtp.port')),
                'mail:mailers:smtp:encryption' => $config->get('mail.mailers.smtp.encryption'),
                'mail:mailers:smtp:username' => $config->get('mail.mailers.smtp.username'),
                // Never return the SMTP password to the client.
                'mail:mailers:smtp:password' => '',
                'mail:from:address' => $config->get('mail.from.address'),
                'mail:from:name' => $config->get('mail.from.name'),
            ],
            'advanced' => [
                'recaptcha:enabled' => JsonValueGuard::boolean($config->get('recaptcha.enabled')),
                'recaptcha:secret_key' => $config->get('recaptcha.secret_key'),
                'recaptcha:website_key' => $config->get('recaptcha.website_key'),
                'pterodactyl:guzzle:timeout' => JsonValueGuard::integer($config->get('pterodactyl.guzzle.timeout')),
                'pterodactyl:guzzle:connect_timeout' => JsonValueGuard::integer($config->get('pterodactyl.guzzle.connect_timeout')),
                'pterodactyl:client_features:allocations:enabled' => JsonValueGuard::boolean($config->get('pterodactyl.client_features.allocations.enabled')),
                'pterodactyl:client_features:allocations:range_start' => $config->get('pterodactyl.client_features.allocations.range_start'),
                'pterodactyl:client_features:allocations:range_end' => $config->get('pterodactyl.client_features.allocations.range_end'),
            ],
            'meta' => [
                'load_environment_only' => JsonValueGuard::boolean($config->get('pterodactyl.load_environment_only', false)),
                'show_recaptcha_warning' => $config->get('recaptcha._shipped_secret_key') === $config->get('recaptcha.secret_key')
                    || $config->get('recaptcha._shipped_website_key') === $config->get('recaptcha.website_key'),
            ],
        ]);
    }
}
