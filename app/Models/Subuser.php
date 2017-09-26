<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;
use Pterodactyl\Events\Subuser\{Created, Creating, Deleted, Deleting};

class Subuser extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Notifiable, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subusers';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['daemonSecret'];

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
        'user_id' => 'integer',
        'server_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'user_id' => 'required',
        'server_id' => 'required',
        'daemonSecret' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'user_id' => 'numeric|exists:users,id',
        'server_id' => 'numeric|exists:servers,id',
        'daemonSecret' => 'string',
    ];

    /**
     * Registering event listeners.
     *
     * @var array
     */
    protected $events = [
        'creating' => Creating::class,
        'created' => Created::class,
        'deleting' => Deleting::class,
        'deleted' => Deleted::class,
    ];

    /**
     * Gets the server associated with a subuser.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the user associated with a subuser.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the permissions associated with a subuser.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
