<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\Carbon;
use Database\Factories\ServerTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $server_id
 * @property int $old_node
 * @property int $new_node
 * @property int $old_allocation
 * @property int $new_allocation
 * @property list<int>|null $old_additional_allocations
 * @property list<int>|null $new_additional_allocations
 * @property bool|null $successful
 * @property bool $archived
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Server $server
 * @property Node $oldNode
 * @property Node $newNode
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class ServerTransfer extends Model
{
    /** @use HasFactory<ServerTransferFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
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
     * Gets the server associated with a server transfer.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the source node associated with a server transfer.
     *
     * @return HasOne<Node, $this>
     */
    public function oldNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'old_node');
    }

    /**
     * Gets the target node associated with a server transfer.
     *
     * @return HasOne<Node, $this>
     */
    public function newNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'new_node');
    }
}
