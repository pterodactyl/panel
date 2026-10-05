<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class UpdateMailSettingsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminSettingsUpdate];
    }

    /**
     * Validation rules for updating mail settings.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'smtp.host' => ['required', 'string'],
            'smtp.port' => ['required', 'integer', 'between:1,65535'],
            'smtp.encryption' => ['present', Rule::in([null, 'tls', 'ssl'])],
            'smtp.username' => ['nullable', 'string', 'max:191'],
            'smtp.password' => ['nullable', 'string', 'max:191'],
            'smtp.from_address' => ['required', 'string', 'email'],
            'smtp.from_name' => ['nullable', 'string', 'max:191'],
        ];
    }

    /**
     * Map smtp.* input onto settings storage keys; password is only included when present so it is never blanked out.
     *
     * @param  list<string>|null  $only
     * @return MailSettingsData
     */
    public function normalize(?array $only = null): array
    {
        $values = [
            'mail:mailers:smtp:host' => $this->string('smtp.host')->toString(),
            'mail:mailers:smtp:port' => $this->integer('smtp.port'),
            'mail:mailers:smtp:encryption' => $this->filled('smtp.encryption') ? $this->string('smtp.encryption')->toString() : null,
            'mail:mailers:smtp:username' => $this->filled('smtp.username') ? $this->string('smtp.username')->toString() : null,
            'mail:from:address' => $this->string('smtp.from_address')->toString(),
            'mail:from:name' => $this->filled('smtp.from_name') ? $this->string('smtp.from_name')->toString() : null,
        ];

        if (! empty($this->input('smtp.password'))) {
            $values['mail:mailers:smtp:password'] = $this->string('smtp.password')->toString();
        }

        return $values;
    }
}
