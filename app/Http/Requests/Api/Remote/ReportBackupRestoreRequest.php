<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Pterodactyl\Support\JsonValueGuard;

class ReportBackupRestoreRequest extends FormRequest
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
            'successful' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array{successful: bool}
     */
    public function payload(): array
    {
        $data = parent::validated();

        return [
            'successful' => JsonValueGuard::boolean(Arr::get($data, 'successful')),
        ];
    }
}
