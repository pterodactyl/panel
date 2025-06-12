<?php

namespace Pterodactyl\Services\Hooks;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\ActionDefinition;
use Illuminate\Support\Collection;

class ActionDefinitionService
{
    /**
     * ActionDefinitionService constructor.
     */
    public function __construct() {}

    public function getAll(): Collection
    {
        return ActionDefinition::all();
    }

    public function findByKey(string $key): ?ActionDefinition
    {
        return ActionDefinition::where('key', $key)->first();
    }

    /**
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateConfig(ActionDefinition $definition, array $config): void
    {
        $schema = $definition->config_schema ?? [];
        $rules = $this->convertSchemaToRules($schema);

        $validator = Validator::make($config, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function convertSchemaToRules(array $schema): array
    {
        $rules = [];
        foreach ($schema as $key => $data) {
            if (is_string($data)) {
                $type = $data;
                $required = true;
                $validate = null;
            } else {
                $type = $data['required'] ?? 'string';
                $required = $data['required'] ?? true;
                $validate = $data['validate'] ?? null;
            }

            $baseRule = match($type) {
                'string' => 'string',
                'integer' => 'integer',
                'boolean' => 'boolean',
                'array' => 'array',
                'numeric' => 'numeric',
                default => 'required'
            };


            $fieldRules = [$required ? 'required' : 'nullable', $baseRule];

            if ($validate === 'regex') {
                $fieldRules[] = function(string $attribute, $value, Closure $fail) {
                    try {
                        preg_match($value, '');
                    } catch (\Throwable $e) {
                        $fail("The {$attribute} field must be a valid regex expression.");
                    }
                };
            }


            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Create a new ActionDefinition.
     *
     * @throws ValidationException
     */

    public function create(array $data): ActionDefinition
    {
        $validator = Validator::make($data, [
            'key' => 'required|string|unique:action_definitions,key',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'config_schema' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return ActionDefinition::create([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'config_schema' => $data['config_schema'],
        ]);
    }

}
