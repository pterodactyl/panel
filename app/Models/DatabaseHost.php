<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class DatabaseHost extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

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
     * Application validation rules.
     *
     * @var array
     */
    protected static $applicationRules = [
        'name' => 'required',
        'host' => 'required',
        'port' => 'required',
        'username' => 'required',
        'node_id' => 'sometimes',
    ];

    /**
     * Validation rules to assign to this model.
     *
     * @var array
     */
    protected static $dataIntegrityRules = [
        'name' => 'string|max:255',
        'host' => 'ip|unique:database_hosts,host',
        'port' => 'numeric|between:1,65535',
        'username' => 'string|max:32',
        'password' => 'nullable|string',
        'node_id' => 'nullable|integer|exists:nodes,id',
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
     * Gets the databases assocaited with this host.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }
}
