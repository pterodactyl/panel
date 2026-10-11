<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Actions\Servers\UpdateServerBuild;
use Pterodactyl\Models\Server;

class UpdateServerBuildConfigurationRequest extends ServerWriteRequest
{
    /**
     * Return the rules to validate this request against. The Application API keeps its own
     * field names and rules; actionInput() hands the values to the action under its names.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = UpdateServerBuild::rules($this->parameter('server', Server::class));

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

            'add_allocations' => $rules['add_allocations'],
            'add_allocations.*' => $rules['add_allocations.*'],
            'remove_allocations' => $rules['remove_allocations'],
            'remove_allocations.*' => $rules['remove_allocations.*'],

            'feature_limits' => ['required', 'array'],
            'feature_limits.databases' => $rules['database_limit'],
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => $rules['backup_limit'],
        ];
    }

    /**
     * The validated input under the action's field names: allocation is its allocation_id,
     * and the feature limits move to its top level. A limit sent both under limits and flat
     * is taken from limits, read with Laravel's typed accessors.
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        $validated = $this->safe();

        $input = [
            'database_limit' => $validated->filled('feature_limits.databases') ? $validated->integer('feature_limits.databases') : null,
            'allocation_limit' => $validated->filled('feature_limits.allocations') ? $validated->integer('feature_limits.allocations') : null,
            'backup_limit' => $validated->filled('feature_limits.backups') ? $validated->integer('feature_limits.backups') : null,
        ];

        if ($validated->filled('allocation')) {
            $input['allocation_id'] = $validated->integer('allocation');
        }

        if ($validated->has('oom_disabled')) {
            $input['oom_disabled'] = $validated->boolean('oom_disabled');
        }

        foreach (['memory', 'swap', 'io', 'cpu', 'disk'] as $limit) {
            if ($validated->filled('limits.'.$limit)) {
                $input[$limit] = $validated->integer('limits.'.$limit);
            } elseif ($validated->filled($limit)) {
                $input[$limit] = $validated->integer($limit);
            }
        }

        if ($validated->has('limits.threads') || $validated->has('threads')) {
            $field = $validated->has('limits.threads') ? 'limits.threads' : 'threads';
            $input['threads'] = $validated->filled($field) ? $validated->string($field)->toString() : null;
        }

        foreach (['add_allocations', 'remove_allocations'] as $list) {
            if ($validated->has($list)) {
                $input[$list] = array_map(fn (int|string $key): int => $validated->integer($list.'.'.$key), array_keys($validated->array($list)));
            }
        }

        return $input;
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
}
