<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\UpdateMailSettingsRequest;
use Pterodactyl\Models\Setting;
use Pterodactyl\Providers\SettingsServiceProvider;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Settings', 'View and update global panel settings.')]
class MailController extends AdminApiController
{
    private const array SMTP_DRIVER_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'This feature is only available if SMTP is the selected email driver for the Panel.',
            ],
        ],
    ];

    /**
     * Update mail settings.
     */
    #[Endpoint('Update mail settings', 'Updates SMTP mail settings and restarts queued workers so they reload configuration.')]
    #[ScribeResponse(status: 204, description: 'Mail settings updated.')]
    #[ScribeResponse(self::SMTP_DRIVER_ERROR, status: 400, description: 'The panel is not configured to use SMTP mail.')]
    public function __invoke(UpdateMailSettingsRequest $request, Kernel $kernel): Response
    {
        throw_if(config('mail.default') !== 'smtp', DisplayException::class, 'This feature is only available if SMTP is the selected email driver for the Panel.');

        $values = $request->normalize();
        if (Arr::get($values, 'mail:mailers:smtp:password') === '!e') {
            $values['mail:mailers:smtp:password'] = '';
        }

        foreach ($values as $key => $value) {
            if (in_array($key, SettingsServiceProvider::getEncryptedKeys()) && ! empty($value)) {
                $value = Crypt::encrypt($value);
            }

            // SAFETY: settings are persisted in the environment store as strings; null retains its deletion semantics.
            Setting::put('settings::'.$key, ($value) === null ? null : (string) $value);
        }

        $kernel->call('queue:restart');

        return $this->returnNoContent();
    }
}
