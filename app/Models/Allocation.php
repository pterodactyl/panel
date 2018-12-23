<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Allocation extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'allocation';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'allocations';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'node_id' => 'integer',
        'port' => 'integer',
        'server_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'node_id' => 'required',
        'ip' => 'required',
        'port' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'node_id' => 'exists:nodes,id',
        'ip' => 'ip',
        'port' => 'numeric|between:1024,65553',
        'ip_alias' => 'nullable|string',
        'server_id' => 'nullable|exists:servers,id',
    ];

    /**
     * Return a hashid encoded string to represent the ID of the allocation.
     *
     * @return string
     */
    public function getHashidAttribute()
    {
        return app()->make('hashids')->encode($this->id);
    }

    /**
     * Accessor to automatically provide the IP alias if defined.
     *
     * @param null|string $value
     * @return string
     */
    public function getAliasAttribute($value)
    {
        return (is_null($this->ip_alias)) ? $this->ip : $this->ip_alias;
    }

    /**
     * Accessor to quickly determine if this allocation has an alias.
     *
     * @param null|string $value
     * @return bool
     */
    public function getHasAliasAttribute($value)
    {
        return ! is_null($this->ip_alias);
    }

    /**
     * Gets information for the server associated with this allocation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Return the Node model associated with this allocation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
