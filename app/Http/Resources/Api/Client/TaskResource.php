<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Task;

/**
 * @extends ClientResource<Task>
 */
#[ResponseField('action', example: 'power', enum: ['power', 'command', 'backup'])]
class TaskResource extends ClientResource
{
    public function resourceName(): string
    {
        return Task::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'sequence_id' => $this->resource->sequence_id,
            'action' => $this->resource->action,
            'payload' => $this->resource->payload,
            'time_offset' => $this->resource->time_offset,
            'is_queued' => $this->resource->is_queued,
            'continue_on_failure' => $this->resource->continue_on_failure,
            'created_at' => $this->resource->created_at->toAtomString(),
            'updated_at' => $this->resource->updated_at->toAtomString(),
        ];
    }
}
