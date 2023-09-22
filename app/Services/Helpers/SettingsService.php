<?php

namespace Pterodactyl\Services\Helpers;

use Pterodactyl\Traits\Helpers\AvailableLanguages;

class SettingsService
{
    use AvailableLanguages;

    /**
     * Return the current version of the panel that is being used.
     */
    public function getCurrentSettings(): array
    {
        return array(
            'general' => [
                'name' => config('app.name'),
                'languages' => $this->getAvailableLanguages(true),
            ],
            'mail' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'password' => config('mail.mailers.smtp.password'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name')
            ],
            'security' => [
                'recaptcha' => [
                    'enabled' => config('recaptcha.enabled'),
                    'site_key' => config('recaptcha.website_key'),
                    'secret_key' => config('recaptcha.secret_key'),
                ],
                '2fa_enabled' => config('pterodactyl.auth.2fa_required'),
            ],
        );
    }
}
