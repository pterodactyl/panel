<?php

namespace Pterodactyl\Services\Helpers;

use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Models\Setting;

class SettingsService
{
    use AvailableLanguages;

    public function __construct(private SettingsRepository $repository) {}

    /**
     * Return the current version of the panel that is being used.
     */
    public function getCurrentSettings(): array
    {
        return array(
            'general' => [
                'name' => $this->repository->get('appName', config('app.name')),
                'language' => $this->repository->get('language', config('app.locale')),
                'languages' => $this->getAvailableLanguages(true),
            ],
            'mail' => [
                'host' => $this->repository->get('smtpHost', config('mail.mailers.smtp.host')),
                'port' => $this->repository->get('smtpPort', config('mail.mailers.smtp.port')),
                'encryption' => $this->repository->get('smtpEncryption', config('mail.mailers.smtp.encryption')),
                'username' => $this->repository->get('smtpUsername', config('mail.mailers.smtp.username')),
                'password' => $this->repository->get('smtpPassword', config('mail.mailers.smtp.password')),
                'from_address' => $this->repository->get('smtpFromAddress', config('mail.from.address')),
                'from_name' => $this->repository->get('smtpFromName', config('mail.from.name')),
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
