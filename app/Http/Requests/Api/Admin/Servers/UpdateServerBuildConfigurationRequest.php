<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;
use Pterodactyl\Validation\ServerRules;
use UnexpectedValueException;

class UpdateServerBuildConfigurationRequest extends ServerWriteRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for updating a server's build configuration.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ServerRules::rules($this->parameter('server', Server::class));

        return [
            'allocation_id' => $rules['allocation_id'],
            'oom_disabled' => $rules['oom_disabled'],

            'memory' => $this->requiredToOptional('memory', $rules['memory']),
            'swap' => $this->requiredToOptional('swap', $rules['swap']),
            'io' => $this->requiredToOptional('io', $rules['io']),
            'cpu' => $this->requiredToOptional('cpu', $rules['cpu']),
            'threads' => $this->requiredToOptional('threads', $rules['threads']),
            'disk' => $this->requiredToOptional('disk', $rules['disk']),

            'add_allocation_ids' => ['bail', 'array'],
            'add_allocation_ids.*' => ['integer'],
            'remove_allocation_ids' => ['bail', 'array'],
            'remove_allocation_ids.*' => ['integer'],

            'database_limit' => $rules['database_limit'],
            'allocation_limit' => $rules['allocation_limit'],
            'backup_limit' => $rules['backup_limit'],
        ];
    }

    /**
     * Rename the allocation id fields into the add/remove keys the service expects.
     *
     * @return ServerBuildModificationData
     */
    public function payload(): array
    {
        $data = [];

        if ($this->has('oom_disabled')) {
            $data['oom_disabled'] = $this->boolean('oom_disabled');
        }

        foreach (['memory', 'swap', 'io', 'cpu', 'disk', 'allocation_id'] as $field) {
            if ($this->filled($field)) {
                $data[$field] = $this->integer($field);
            }
        }

        if ($this->has('threads')) {
            $data['threads'] = $this->filled('threads') ? $this->string('threads')->toString() : null;
        }

        foreach (['database_limit', 'allocation_limit', 'backup_limit'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->filled($field) ? $this->integer($field) : null;
            }
        }

        if ($this->has('add_allocation_ids')) {
            $data['add_allocations'] = $this->integerList('add_allocation_ids');
        }

        if ($this->has('remove_allocation_ids')) {
            $data['remove_allocations'] = $this->integerList('remove_allocation_ids');
        }

        return $data;
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'add_allocation_ids' => 'allocations to add',
            'remove_allocation_ids' => 'allocations to remove',
            'add_allocation_ids.*' => 'allocation to add',
            'remove_allocation_ids.*' => 'allocation to remove',
            'database_limit' => 'Database Limit',
            'allocation_limit' => 'Allocation Limit',
            'backup_limit' => 'Backup Limit',
        ];
    }

    /**
     * Relax a `required` limit rule into required_with/without:limits for backwards compat. @see https://github.com/pterodactyl/panel/issues/1500
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
