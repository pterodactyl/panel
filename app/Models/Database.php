<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Database extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'server_database';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'databases';

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
        'server_id', 'database_host_id', 'database', 'username', 'password', 'remote',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'server_id' => 'integer',
        'database_host_id' => 'integer',
    ];

    protected static $applicationRules = [
        'server_id' => 'required',
        'database_host_id' => 'required',
        'database' => 'required',
        'remote' => 'required',
    ];

    protected static $dataIntegrityRules = [
        'server_id' => 'numeric|exists:servers,id',
        'database_host_id' => 'exists:database_hosts,id',
        'database' => 'string|alpha_dash|between:3,100',
        'username' => 'string|alpha_dash|between:3,100',
        'remote' => 'string|regex:/^[0-9%.]{1,15}$/',
        'password' => 'string',
    ];

    /**
     * Gets the host database server associated with a database.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function host()
    {
        return $this->belongsTo(DatabaseHost::class, 'database_host_id');
    }

    /**
     * Gets the server associated with a database.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
