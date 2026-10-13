<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;

class ReportBackupRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Whether the restore succeeded only picks the activity event that is logged.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'successful' => ['required', 'boolean'],
        ];
    }
}
