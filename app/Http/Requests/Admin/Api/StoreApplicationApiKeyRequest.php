<?php

namespace Pterodactyl\Http\Requests\Admin\Api;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreApplicationApiKeyRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $modelRules = ApiKey::getCreateRules();

        return collect(AdminAcl::getResourceList())->mapWithKeys(function ($resource) use ($modelRules) {
            return [AdminAcl::COLUMN_IDENTIFER . $resource => $modelRules['r_' . $resource]];
        })->merge(['memo' => $modelRules['memo']])->toArray();
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'memo' => 'Description',
        ];
    }

    public function getKeyPermissions(): array
    {
        return collect($this->validated())->filter(function ($value, $key) {
            return substr($key, 0, strlen(AdminAcl::COLUMN_IDENTIFER)) === AdminAcl::COLUMN_IDENTIFER;
        })->toArray();
    }
}
