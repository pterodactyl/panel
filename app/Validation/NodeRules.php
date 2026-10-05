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
            'memory' => ['required', 'numeric', 'min:1'],
            'memory_overallocate' => ['required', 'numeric', 'min:-1'],
            'disk' => ['required', 'numeric', 'min:1'],
            'disk_overallocate' => ['required', 'numeric', 'min:-1'],
            'daemonBase' => ['sometimes', 'required', 'regex:/^([\/][\d\w.\-\/]+)$/'],
            'daemonSFTP' => ['required', 'numeric', 'between:1,65535'],
            'daemonListen' => ['required', 'numeric', 'between:1,65535'],
            'maintenance_mode' => ['boolean'],
            'upload_size' => ['int', 'min:1'],
        ];
    }
}
