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
           // General
           'appName' => 'sometimes|required|string|max:191',
           'language' => ['sometimes', 'required', 'string', Rule::in(array_keys($this->getAvailableLanguages()))],

           // Mail
           'smtpHost' => 'sometimes|required|string',
           'smtpPort' => 'sometimes|required|integer|between:1,65535',
           'smtpEncryption' => ['sometimes', 'present', Rule::in([null, 'tls', 'ssl'])],
           'smtpUsername' => 'sometimes|nullable|string|max:191',
           'smtpPassword' => 'sometimes|nullable|string|max:191',
           'smtpMailFrom' => 'sometimes|required|string|email',
           'smtpMailFromName' => 'sometimes|nullable|string|max:191',

           // Security
           'recaptchaEnabled' => 'sometimes|required|boolean',
           'recaptchaSiteKey' => 'sometimes|required_if:recaptchaEnabled,true|string|max:191',
           'recaptchaSecretKey' => 'sometimes|required_if:recaptchaEnabled,true|string|max:191',
           'sfaEnabled' => 'sometimes|required|integer|between:0,2',
       ];
    }
}
