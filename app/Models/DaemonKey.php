<?php

namespace Pterodactyl\Models;

use Znck\Eloquent\Traits\BelongsToThrough;

class DaemonKey extends Model
{
    use BelongsToThrough;

    /**
     * @var string
     */
    protected $table = 'daemon_keys';

    /**
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'server_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        'expires_at',
    ];

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'server_id', 'secret', 'expires_at'];

    /**
     * @var array
     */
    public static $validationRules = [
        'user_id' => 'required|numeric|exists:users,id',
        'server_id' => 'required|numeric|exists:servers,id',
        'secret' => 'required|string|min:20',
        'expires_at' => 'required|date',
    ];

    /**
     * Return the server relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Return the node relation.
     *
     * @return \Znck\Eloquent\Relations\BelongsToThrough
     * @throws \Exception
     */
    public function node()
    {
        return $this->belongsToThrough(Node::class, Server::class);
    }

    /**
     * Return the user relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
