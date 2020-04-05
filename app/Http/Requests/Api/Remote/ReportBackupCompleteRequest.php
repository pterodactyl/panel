<?php

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;

class ReportBackupCompleteRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'successful' => 'boolean',
            'sha256_hash' => 'string|required_if:successful,true',
            'file_size' => 'numeric|required_if:successful,true',
        ];
    }
}
