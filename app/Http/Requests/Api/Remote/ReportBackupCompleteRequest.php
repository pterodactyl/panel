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
            'successful' => 'required|boolean',
            'checksum' => 'nullable|string|required_if:successful,true',
            'checksum_type' => 'nullable|string|required_if:successful,true',
            'size' => 'nullable|numeric|required_if:successful,true',
        ];
    }
}
