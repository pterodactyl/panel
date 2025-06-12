<?php

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Item;
use Pterodactyl\Models\Hook;
use League\Fractal\Resource\Collection;
use Pterodactyl\Transformers\Api\Application\TriggerTransformer;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;
class HookTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['trigger'];

    protected array $defaultIncludes = ['trigger'];

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return Hook::RESOURCE_NAME;
    }

    /**
     * Returns a transformed schedule model such that a client can view the information.
     */
    public function transform(Hook $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'enabled' => $model->enabled,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

    /**
     * Allows attaching the tasks specific to the schedule in the response.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeTrigger(Hook $model): ?Item
    {
        if (!$model->trigger) {
            return null;
        }
        return $this->item(
            $model->trigger,
            $this->makeTransformer(TriggerTransformer::class),
            Hook::RESOURCE_NAME
        );
    }
}
