<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Hook;
use Pterodactyl\Models\HookTrigger;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use League\Fractal\Resource\Collection;

class TriggerTransformer extends BaseClientTransformer
{

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return HookTrigger::RESOURCE_NAME;
    }

    /**
     * Transforms a hook's trigger to a client viewable format.
     */
    public function transform(HookTrigger $model): array
    {
        return [
            'id' => $model->id,
            'type' => $model->type,
            'config' => $model->config,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

}
