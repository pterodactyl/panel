<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

class ReportBackupCompleteRequest extends FormRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'successful' => ['required', 'boolean'],
            'checksum' => ['nullable', 'string', 'required_if:successful,true'],
            'checksum_type' => ['nullable', 'string', 'required_if:successful,true'],
            'size' => ['nullable', 'numeric', 'required_if:successful,true'],
            'parts' => ['nullable', 'array', 'list'],
            'parts.*.etag' => ['required', 'string'],
            'parts.*.part_number' => ['required', 'integer'],
        ];
    }

    /**
     * @return BackupCompletionData
     */
    public function payload(): array
    {
        return [
            'successful' => $this->boolean('successful'),
            'checksum' => JsonValueGuard::nullableString($this->input('checksum')),
            'checksum_type' => JsonValueGuard::nullableString($this->input('checksum_type')),
            'size' => $this->integer('size'),
            'parts' => $this->parts(),
        ];
    }

    /**
     * @return list<MultipartUploadPart>|null
     */
    private function parts(): ?array
    {
        $parts = $this->input('parts');
        if ($parts === null) {
            return null;
        }

        throw_if(! is_array($parts) || ! array_is_list($parts), UnexpectedValueException::class, 'Multipart upload parts must be a list.');

        $normalized = [];
        foreach ($parts as $part) {
            throw_unless(is_array($part), UnexpectedValueException::class, 'Each multipart upload part must be a string-keyed array.');

            $normalized[] = [
                'etag' => JsonValueGuard::string($part['etag'] ?? null),
                'part_number' => JsonValueGuard::integer($part['part_number'] ?? null),
            ];
        }

        return $normalized;
    }
}
