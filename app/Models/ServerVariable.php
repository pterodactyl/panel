<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int $variable_id
 * @property string $variable_value
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property EggVariable $variable
 * @property Server $server
 */
class ServerVariable extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_variable';

    protected bool $immutableDates = true;

    protected $table = 'server_variables';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'server_id' => 'integer',
        'variable_id' => 'integer',
    ];

    public static array $validationRules = [
        'server_id' => 'required|int',
        'variable_id' => 'required|int',
        'variable_value' => 'string',
    ];

    /**
     * Returns the server this variable is associated with.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Returns information about a given variables parent.
     */
    public function variable(): BelongsTo
    {
        return $this->belongsTo(EggVariable::class, 'variable_id');
    }
}
