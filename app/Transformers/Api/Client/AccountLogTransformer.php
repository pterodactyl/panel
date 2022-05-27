<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\AccountLog;

class AccountLogTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return AccountLog::RESOURCE_NAME;
    }

    /**
     * Return the account logs for the current user.
     * 
     * @return array
     */
    public function transform(AccountLog $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->user_id,
            'action' => $model->action,
            'ip_address' => $model->ip_address,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}