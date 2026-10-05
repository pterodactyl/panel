<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

class ActivityEventRequest extends FormRequest
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
            'data' => ['required', 'array'],
            'data.*' => ['array'],
            'data.*.user' => ['sometimes', 'nullable', 'uuid'],
            'data.*.server' => ['required', 'uuid'],
            'data.*.event' => ['required', 'string'],
            'data.*.metadata' => ['present', 'nullable', 'array'],
            'data.*.ip' => ['sometimes', 'nullable', 'ip'],
            'data.*.timestamp' => ['required', 'string'],
        ];
    }

    /**
     * The activity events submitted with this request. The "data" key is
     * validated as a required array before these accessors are reachable; the
     * guard is what keeps the type honest, since `input()` returns mixed.
     *
     * @return ActivityEvents
     */
    public function events(): array
    {
        $data = $this->input('data');
        JsonValueGuard::assertValue($data);
        throw_unless(is_array($data), UnexpectedValueException::class, 'Activity event data must be a list.');

        $events = [];
        foreach ($data as $value) {
            throw_unless(is_array($value), UnexpectedValueException::class, 'Each activity event must be an object.');

            $metadata = $value['metadata'] ?? [];
            throw_unless(is_array($metadata), UnexpectedValueException::class, 'Activity event metadata must be an object.');

            $normalizedMetadata = [];
            foreach ($metadata as $key => $item) {
                throw_unless(is_string($key), UnexpectedValueException::class, 'Activity event metadata keys must be strings.');

                $normalizedMetadata[$key] = $item;
            }

            $events[] = [
                'user' => $this->nullableString($value['user'] ?? null, 'user'),
                'server' => $this->requiredString($value['server'] ?? null, 'server'),
                'event' => $this->requiredString($value['event'] ?? null, 'event'),
                'metadata' => $normalizedMetadata,
                'ip' => $this->nullableString($value['ip'] ?? null, 'ip'),
                'timestamp' => $this->requiredString($value['timestamp'] ?? null, 'timestamp'),
            ];
        }

        return $events;
    }

    public function node(): Node
    {
        return RemoteRequestNode::get($this);
    }

    /** @param JsonInputValue $value */
    private function requiredString(mixed $value, string $key): string
    {
        if (! is_string($value)) {
            throw new UnexpectedValueException(sprintf('Activity event field "%s" must be a string.', $key));
        }

        return $value;
    }

    /** @param JsonInputValue $value */
    private function nullableString(mixed $value, string $key): ?string
    {
        return $value === null ? null : $this->requiredString($value, $key);
    }
}
