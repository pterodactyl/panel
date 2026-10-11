<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class NodeRules
{
    /**
     * Validation rules for the node requests and p:node:make, keyed by model column.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'regex:/^([\w .-]{1,100})$/'],
            'description' => ['string', 'nullable'],
            'location_id' => ['required', 'exists:locations,id'],
            'public' => ['boolean'],
            'fqdn' => ['required', 'string'],
            'scheme' => ['required'],
            'behind_proxy' => ['boolean'],
            'memory' => ['required', 'integer', 'min:1'],
            'memory_overallocate' => ['required', 'integer', 'min:-1'],
            'disk' => ['required', 'integer', 'min:1'],
            'disk_overallocate' => ['required', 'integer', 'min:-1'],
            'daemonBase' => ['sometimes', 'required', 'regex:/^([\/][\d\w.\-\/]+)$/'],
            'daemonSFTP' => ['required', 'integer', 'between:1,65535'],
            'daemonListen' => ['required', 'integer', 'between:1,65535'],
            'maintenance_mode' => ['boolean'],
            'upload_size' => ['int', 'min:1'],
        ];
    }
}
