<?php

namespace Pterodactyl\Http\Requests\Api\Application\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Traits\Helpers\AvailableLanguages;

class UpdateSettingsRequest extends ApplicationApiRequest
{
    use AvailableLanguages;

    public function rules(): array
    {
       return [
           'appName' => 'sometimes|required|string|max:191',
           'language' => ['sometimes', 'required', 'string', Rule::in(array_keys($this->getAvailableLanguages()))],
           'smtpHost' => 'sometimes|required|string',
           'smtpPort' => 'sometimes|required|integer|between:1,65535',
           'smtpEncryption' => ['sometimes', 'present', Rule::in([null, 'tls', 'ssl'])],
           'username' => 'sometimes|nullable|string|max:191',
           'password' => 'sometimes|nullable|string|max:191',
           'mailFrom' => 'sometimes|required|string|email',
           'mailFromName' => 'sometimes|nullable|string|max:191',
       ];
    }
}
