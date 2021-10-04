<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property int $server_id
 * @property int $variable_id
 * @property string $variable_value
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Pterodactyl\Models\EggVariable $variable
 * @property \Pterodactyl\Models\Server $server
 */
class ServerVariable extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_variable';

    /** @var bool */
    protected $immutableDates = true;

    /** @var string */
    protected $table = 'server_variables';

    /** @var string[] */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /** @var string[] */
    protected $casts = [
        'server_id' => 'integer',
        'variable_id' => 'integer',
    ];

    /** @var string[] */
    public static $validationRules = [
        'server_id' => 'required|int',
        'variable_id' => 'required|int',
        'variable_value' => 'string',
    ];

    /**
     * Returns the server this variable is associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Returns information about a given variables parent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variable()
    {
        return $this->belongsTo(EggVariable::class, 'variable_id');
    }
}
