<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client;

use Illuminate\Validation\Rule;

class GetServersRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', 'string', Rule::in(['owner', 'admin', 'admin-all'])],
        ];
    }
}
