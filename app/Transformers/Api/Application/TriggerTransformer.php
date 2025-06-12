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

    protected function fixConfig(string $type, array $config): array
    {
        return match ($type) {
            'console_match' => [
                'webhook_url' => $config[0] ?? null,
                'message' => $config[1] ?? null,
            ],
            'power_changed' => [
                'to' => $config[0] ?? null,
                'subject' => $config[1] ?? null,
                'message' => $config[2] ?? null,
            ],
            'high_stats' => [
                'schedule' => $config[0] ?? null,
            ],
            default => $config,
        };
    }


    /**
     * Transforms a hook's trigger to a client viewable format.
     */
    public function transform(HookTrigger $model): array
    {
        return [
            'id' => $model->id,
            'type' => $model->type,
            'config' => $this->fixConfig($model->type,$model->config),
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

}
