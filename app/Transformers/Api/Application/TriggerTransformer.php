<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Hook;
use Pterodactyl\Models\HookTrigger;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\Schedule;
use League\Fractal\Resource\Collection;

class TriggerTransformer extends BaseTransformer
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
                'pattern' => $config[0] ?? null,
            ],
            'power_changed' => [
                'status' => $config[0] ?? null,
            ],
            'high_stats' => [
                'type' => $config[0] ?? null,
                'threshold' => $config[1] ?? null,
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
