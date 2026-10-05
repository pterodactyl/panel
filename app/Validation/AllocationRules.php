<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

final class AllocationRules
{
    /**
     * The allocation exists and no server holds it.
     */
    public static function unassigned(): Exists
    {
        return Rule::exists('allocations', 'id')->where(function (Builder $query): void {
            $query->whereNull('server_id');
        });
    }

    /**
     * Validation rules for allocation attributes, consumed by the requests that
     * accept them.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'node_id' => ['required', 'exists:nodes,id'],
            'ip' => ['required', 'ip'],
            'port' => ['required', 'numeric', 'between:1024,65535'],
            'ip_alias' => ['nullable', 'string'],
            'server_id' => ['nullable', 'exists:servers,id'],
            'notes' => ['nullable', 'string', 'max:256'],
        ];
    }
}
