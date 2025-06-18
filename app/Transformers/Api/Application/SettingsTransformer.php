<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Setting;
use Pterodactyl\Transformers\Api\Transformer;

class SettingsTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Setting::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server variable array.
     */
    public function transform(array $model): array
    {
        return [
            'general' => [
                'name' => $model['general']['name'],
                'language' => $model['general']['language'],
                'languages' => $model['general']['languages'],
            ],
            'mail' => [
                'host' => $model['mail']['host'],
                'port' => $model['mail']['port'],
                'from_address' => $model['mail']['from_address'],
                'from_name' => $model['mail']['from_name'],
                'encryption' => $model['mail']['encryption'],
                'username' => $model['mail']['username'],
                'password' => $model['mail']['password'],
            ],
            'security' => [
                'recaptcha' => [
                    'enabled' => $model['security']['recaptcha']['enabled'],
                    'site_key' => $model['security']['recaptcha']['site_key'],
                    'secret_key' => $model['security']['recaptcha']['secret_key'],
                ],
                '2fa_enabled' => $model['security']['2fa_enabled'],
            ],
        ];
    }
}
