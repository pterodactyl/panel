<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\ApiKeys;

use Pterodactyl\Actions\Api\CreateApiKey;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class StoreApiKeyRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminApiKeysCreate];
    }

    /**
     * Validation rules for creating an application API key: its memo, then its resource
     * permissions.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return ['memo' => CreateApiKey::rules()['memo'], ...CreateApiKey::permissionRules()];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'memo' => 'Description',
        ];
    }

    /**
     * The validated input without the resource permissions, which getKeyPermissions()
     * returns.
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        return array_diff_key(parent::actionInput(), CreateApiKey::permissionRules());
    }

    /**
     * The validated resource permissions, keyed by r_* column, for the action to read.
     *
     * @return ActionInput
     */
    public function getKeyPermissions(): array
    {
        return array_intersect_key(parent::actionInput(), CreateApiKey::permissionRules());
    }
}
