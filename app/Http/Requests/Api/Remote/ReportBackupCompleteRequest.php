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
            'checksum' => 'string|required_if:successful,true',
            'size' => 'numeric|required_if:successful,true',
        ];
    }
}
