<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    public function scopePermission($query, $permission)
    {
        return $query->where('permission', $permission);
    }

    public function scopeServer($query, $server)
    {
        return $query->where('server_id', $server->id);
    }

}
