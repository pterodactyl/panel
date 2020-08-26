<?php

use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;
use Illuminate\Database\Eloquent\Model;

class MountServer extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $table = 'mount_server';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mount()
    {
        return $this->belongsTo(Mount::class);
    }
}
