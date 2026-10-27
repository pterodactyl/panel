<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Pterodactyl\Actions\Users\CreateUser;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class UpdateLanguageRequest extends ClientApiRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'language' => ['present', ...CreateUser::rules($this->user())['language']],
        ];
    }

    public function language(): ?string
    {
        $language = $this->validated('language');

        return is_string($language) ? $language : null;
    }
}
