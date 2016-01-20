<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Subuser extends Model
{

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
     * @var mixed
     */
    protected static $user;

    /**
     * Constructor
     */
    public function __construct()
    {
        self::$user = Auth::user();
    }

    /**
     * Returns an array of each server ID that the user has access to.
     *
     * @return array
     */
    public static function accessServers()
    {

        $access = [];

        $union = self::select('server_id')->where('user_id', self::$user->id);
        $select = Server::select('id')->where('owner', self::$user->id)->union($union)->get();

        foreach($select as &$select) {
            $access = array_merge($access, [ $select->id ]);
        }

        return $access;

    }

}
