<?php

namespace Pterodactyl\Models;

use Debugbar;
use Illuminate\Database\Eloquent\Model;

class APIPermission extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_permissions';

    /**
     * Checks if an API key has a specific permission.
     *
     * @param  int $id
     * @param  string $permission
     * @return boolean
     */
    public static function check($id, $permission)
    {
        return self::where('key_id', $id)->where('permission', $permission)->exists();
    }

}
