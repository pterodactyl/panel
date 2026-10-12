<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\ServerTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Server $server
 * @property-read Node $newNode
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class ServerTransfer extends Model
{
    /** @use HasFactory<ServerTransferFactory> */
    use HasFactory;

    public const string RESOURCE_NAME = 'server_transfer';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'server_id' => 'int',
            'old_node' => 'int',
            'new_node' => 'int',
            'old_allocation' => 'int',
            'new_allocation' => 'int',
            'old_additional_allocations' => 'array',
            'new_additional_allocations' => 'array',
            'successful' => 'bool',
            'archived' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasOne<Node, $this>
     */
    public function oldNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'old_node');
    }

    /**
     * @return HasOne<Node, $this>
     */
    public function newNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'new_node');
    }
}
