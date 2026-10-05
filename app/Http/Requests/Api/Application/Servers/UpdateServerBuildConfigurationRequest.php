<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Validation\ServerRules;
use UnexpectedValueException;

class UpdateServerBuildConfigurationRequest extends ServerWriteRequest
{
    /**
     * Return the rules to validate this request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ServerRules::rules($this->parameter('server', Server::class));

        return [
            'allocation' => $rules['allocation_id'],
            'oom_disabled' => $rules['oom_disabled'],

            'limits' => ['sometimes', 'array'],
            'limits.memory' => $this->requiredToOptional('memory', $rules['memory'], true),
            'limits.swap' => $this->requiredToOptional('swap', $rules['swap'], true),
            'limits.io' => $this->requiredToOptional('io', $rules['io'], true),
            'limits.cpu' => $this->requiredToOptional('cpu', $rules['cpu'], true),
            'limits.threads' => $this->requiredToOptional('threads', $rules['threads'], true),
            'limits.disk' => $this->requiredToOptional('disk', $rules['disk'], true),

            // Legacy rules to maintain backwards compatable API support without requiring
            // a major version bump.
            //
            // @see https://github.com/pterodactyl/panel/issues/1500
            'memory' => $this->requiredToOptional('memory', $rules['memory']),
            'swap' => $this->requiredToOptional('swap', $rules['swap']),
            'io' => $this->requiredToOptional('io', $rules['io']),
            'cpu' => $this->requiredToOptional('cpu', $rules['cpu']),
            'threads' => $this->requiredToOptional('threads', $rules['threads']),
            'disk' => $this->requiredToOptional('disk', $rules['disk']),

            'add_allocations' => ['bail', 'array'],
            'add_allocations.*' => ['integer'],
            'remove_allocations' => ['bail', 'array'],
            'remove_allocations.*' => ['integer'],

            'feature_limits' => ['required', 'array'],
            'feature_limits.databases' => $rules['database_limit'],
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => $rules['backup_limit'],
        ];
    }

    /**
     * Convert the allocation field into the expected format for the service handler.
     *
     * @return ServerBuildModificationData
     */
    public function payload(): array
    {
        $data = [
            'database_limit' => $this->filled('feature_limits.databases') ? $this->integer('feature_limits.databases') : null,
            'allocation_limit' => $this->filled('feature_limits.allocations') ? $this->integer('feature_limits.allocations') : null,
            'backup_limit' => $this->filled('feature_limits.backups') ? $this->integer('feature_limits.backups') : null,
        ];

        if ($this->filled('allocation')) {
            $data['allocation_id'] = $this->integer('allocation');
        }

        if ($this->has('oom_disabled')) {
            $data['oom_disabled'] = $this->boolean('oom_disabled');
        }

        foreach (['memory', 'swap', 'io', 'cpu', 'disk'] as $field) {
            if ($this->filled("limits.{$field}")) {
                $data[$field] = $this->integer("limits.{$field}");
            } elseif ($this->filled($field)) {
                $data[$field] = $this->integer($field);
            }
        }

        if ($this->has('limits.threads') || $this->has('threads')) {
            $field = $this->has('limits.threads') ? 'limits.threads' : 'threads';
            $data['threads'] = $this->filled($field) ? $this->string($field)->toString() : null;
        }

        foreach (['add_allocations', 'remove_allocations'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->integerList($field);
            }
        }

        return $data;
    }

    /**
     * Custom attributes to use in error message responses.
     */
    public function attributes(): array
    {
        return [
            'add_allocations' => 'allocations to add',
            'remove_allocations' => 'allocations to remove',
            'add_allocations.*' => 'allocation to add',
            'remove_allocations.*' => 'allocation to remove',
            'feature_limits.databases' => 'Database Limit',
            'feature_limits.allocations' => 'Allocation Limit',
            'feature_limits.backups' => 'Backup Limit',
        ];
    }

    /**
     * Converts existing rules for certain limits into a format that maintains backwards
     * compatability with the old API endpoint while also supporting a more correct API
     * call.
     *
     * @see https://github.com/pterodactyl/panel/issues/1500
     *
     * @param  ValidationRuleSet  $rules
     * @return ValidationRuleSet
     */
    protected function requiredToOptional(string $field, array $rules, bool $limits = false): array
    {
        if (! in_array('required', $rules, true)) {
            return $rules;
        }

        $optional = [$limits ? 'required_with:limits' : 'required_without:limits'];
        foreach ($rules as $rule) {
            if ($rule !== 'required') {
                $optional[] = $rule;
            }
        }

        return $optional;
    }

    /** @return list<int> */
    private function integerList(string $field): array
    {
        $values = $this->input($field, []);
        throw_if(! is_array($values) || ! array_is_list($values), UnexpectedValueException::class, "The validated [{$field}] field must be a list.");

        $integers = [];
        foreach ($values as $value) {
            throw_unless(is_int($value), UnexpectedValueException::class, "The validated [{$field}] field must contain integers.");

            $integers[] = $value;
        }

        return $integers;
    }
}
