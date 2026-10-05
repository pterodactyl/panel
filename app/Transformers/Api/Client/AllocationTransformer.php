<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Allocation;
use UnexpectedValueException;

#[ResponseField('notes', example: 'Primary game port', nullable: true)]
class AllocationTransformer extends BaseClientTransformer
{
    protected array $eagerLoads = ['server'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'allocation';
    }

    /**
     * @return ApiPayload
     */
    public function transform(Allocation $model): array
    {
        $model->loadMissing('server');
        $server = $model->server ?? throw new UnexpectedValueException('The allocation does not belong to a server.');

        return [
            'id' => $model->id,
            'ip' => $model->ip,
            'ip_alias' => $model->ip_alias,
            'port' => $model->port,
            'notes' => $model->notes,
            'is_default' => $server->allocation_id === $model->id,
        ];
    }
}
