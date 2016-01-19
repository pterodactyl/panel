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

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function scopePermission($query, $permission)
    {
        return $query->where('permission', $permission);
    }

    public function scopeServer($query, $server)
    {
        return $query->where('server_id', $server->id);
    }

}
