<?php

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;

class ReportBackupCompleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'successful' => 'required|boolean',
            'checksum' => 'nullable|string|required_if:successful,true',
            'checksum_type' => 'nullable|string|required_if:successful,true',
            'size' => 'nullable|numeric|required_if:successful,true',
            'parts' => 'nullable|array',
            'parts.*.etag' => 'required|string',
            'parts.*.part_number' => 'required|numeric',
        ];
    }
}
