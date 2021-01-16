<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatabaseHost extends Model
{
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'database_host';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'database_hosts';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password', 'max_databases', 'node_id',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'max_databases' => 'integer',
        'node_id' => 'integer',
    ];

    /**
     * Validation rules to assign to this model.
     *
     * @var array
     */
    public static $validationRules = [
        'name' => 'required|string|max:191',
        'host' => 'required|string',
        'port' => 'required|numeric|between:1,65535',
        'username' => 'required|string|max:32',
        'password' => 'nullable|string',
        'node_id' => 'sometimes|nullable|integer|exists:nodes,id',
    ];

    /**
     * Gets the node associated with a database host.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets the databases associated with this host.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }
}
