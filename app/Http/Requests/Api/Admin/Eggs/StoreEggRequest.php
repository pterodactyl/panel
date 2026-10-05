<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Illuminate\Validation\Rule;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Egg;

class StoreEggRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsCreate];
    }

    /**
     * Validation rules for creating a top-level egg.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'docker_images' => ['required', 'string', 'regex:/^[\w#\.\/\- ]*\|?~?[\w\.\/\-:@ ]*$/im'],
            'force_outgoing_ip' => ['sometimes', 'boolean'],
            'file_denylist' => ['array'],
            'features' => ['sometimes', 'array'],
            'startup' => ['required', 'string'],
            'config_from' => ['sometimes', 'bail', 'nullable', 'integer', Rule::exists(Egg::class, 'id')],
            'config_stop' => ['required_without:config_from', 'nullable', 'string', 'max:191'],
            'config_startup' => ['required_without:config_from', 'nullable', 'json'],
            'config_logs' => ['required_without:config_from', 'nullable', 'json'],
            'config_files' => ['required_without:config_from', 'nullable', 'json'],
        ];
    }

    /**
     * Normalize docker_images and apply defaults before handing data to the service.
     *
     * @return EggCreationData
     */
    public function payload(): array
    {
        $data = [
            'name' => $this->string('name')->toString(),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
            'docker_images' => $this->normalizeDockerImages($this->string('docker_images')->toString()),
            'force_outgoing_ip' => $this->boolean('force_outgoing_ip'),
            'features' => $this->stringList('features'),
            'startup' => $this->string('startup')->toString(),
        ];

        if ($this->has('file_denylist')) {
            $data['file_denylist'] = $this->stringList('file_denylist');
        }

        if ($this->has('config_from')) {
            $data['config_from'] = $this->filled('config_from') ? $this->integer('config_from') : null;
        }

        foreach (['config_stop', 'config_startup', 'config_logs', 'config_files'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->filled($field) ? $this->string($field)->toString() : null;
            }
        }

        return $data;
    }

    /** A zero config_from is the legacy "no parent egg" sentinel; treat it as null. */
    protected function prepareForValidation(): void
    {
        if ($this->has('config_from') && filter_var($this->input('config_from'), FILTER_VALIDATE_INT) === 0) {
            $this->merge(['config_from' => null]);
        }
    }

    /**
     * Normalize a newline-delimited docker image string into a name => image array.
     *
     * @return array<string, string>
     */
    protected function normalizeDockerImages(?string $input = null): array
    {
        $data = array_map(trim(...), explode("\n", $input ?? ''));

        $images = [];
        foreach ($data as $value) {
            $parts = explode('|', $value, 2);
            $images[$parts[0]] = empty($parts[1]) ? $parts[0] : $parts[1];
        }

        return $images;
    }

    /** @return list<string> */
    private function stringList(string $field): array
    {
        $values = $this->input($field, []);
        if (! is_array($values) || ! array_is_list($values)) {
            return [];
        }

        $strings = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $strings[] = $value;
            }
        }

        return $strings;
    }
}
