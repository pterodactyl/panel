<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int $old_node
 * @property int $new_node
 * @property int $old_allocation
 * @property int $new_allocation
 * @property array|null $old_additional_allocations
 * @property array|null $new_additional_allocations
 * @property bool|null $successful
 * @property bool $archived
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\Node $oldNode
 * @property \Pterodactyl\Models\Node $newNode
 */
class ServerTransfer extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_transfer';

    /**
     * The table associated with the model.
     */
    protected $table = 'server_transfers';

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
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

    public static array $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'old_node' => 'required|numeric',
        'new_node' => 'required|numeric',
        'old_allocation' => 'required|numeric',
        'new_allocation' => 'required|numeric',
        'old_additional_allocations' => 'nullable|array',
        'old_additional_allocations.*' => 'numeric',
        'new_additional_allocations' => 'nullable|array',
        'new_additional_allocations.*' => 'numeric',
        'successful' => 'sometimes|nullable|boolean',
    ];

    /**
     * Gets the server associated with a server transfer.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the source node associated with a server transfer.
     */
    public function oldNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'old_node');
    }

    /**
     * Gets the target node associated with a server transfer.
     */
    public function newNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'new_node');
    }
}
