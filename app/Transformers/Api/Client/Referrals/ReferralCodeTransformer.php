<?php

namespace Pterodactyl\Transformers\Api\Client\Referrals;

use Pterodactyl\Models\ReferralCode;
use Pterodactyl\Transformers\Api\Client\BaseClientTransformer;

class ReferralCodeTransformer extends BaseClientTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return ReferralCode::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     *
     * @return array
     */
    public function transform(ReferralCode $model)
    {
        return [
            'code' => $model->code,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}
