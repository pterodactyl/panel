<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $username
 * @property string $password
 * @property int|null $max_databases
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 */
class DatabaseHost extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'database_host';

    protected bool $immutableDates = true;

    /**
     * The table associated with the model.
     */
    protected $table = 'database_hosts';

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = ['password'];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password', 'max_databases',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'id' => 'integer',
        'max_databases' => 'integer',
    ];

    /**
     * Validation rules to assign to this model.
     */
    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'host' => 'required|string',
        'port' => 'required|numeric|between:1,65535',
        'username' => 'required|string|max:32',
        'password' => 'nullable|string',
    ];

    /**
     * Gets the databases associated with this host.
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Returns the nodes that a database host is assigned to.
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class);
    }
}
