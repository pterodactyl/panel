<?php

namespace Pterodactyl\Http\Requests\Admin\Servers\Databases;

use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreServerDatabaseRequest extends AdminFormRequest
{
    /**
     * Validation rules for database creation.
     */
    public function rules(): array
    {
        return [
            'database' => [
                'required',
                'string',
                'min:1',
                'max:24',
                Rule::unique('databases')->where(function (Builder $query) {
                    $query->where('database_host_id', $this->input('database_host_id') ?? 0);
                }),
            ],
            'max_connections' => 'nullable',
            'remote' => 'required|string|regex:/^[0-9%.]{1,15}$/',
            'database_host_id' => 'required|integer|exists:database_hosts,id',
        ];
    }
}
