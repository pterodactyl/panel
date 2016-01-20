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

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nodes';

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
     * @var array
     */
    protected static $guzzle = [];

    /**
     * @var array
     */
    protected static $nodes = [];

    /**
     * Returns an instance of the database object for the requested node ID.
     *
     * @param  int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function getByID($id)
    {

        // The Node is already cached.
        if (array_key_exists($id, self::$nodes)) {
            return self::$nodes[$id];
        }

        self::$nodes[$id] = Node::where('id', $id)->first();
        return self::$nodes[$id];

    }

    /**
     * Returns a Guzzle Client for the node in question.
     *
     * @param  int $node
     * @return \GuzzleHttp\Client
     */
    public static function guzzleRequest($node)
    {

        // The Guzzle Client is cached already.
        if (array_key_exists($node, self::$guzzle)) {
            return self::$guzzle[$node];
        }

        $nodeData = self::getByID($node);

        // @TODO: Better solution to disabling verification. Security risk.
        self::$guzzle[$node] = new Client([
            'base_uri' => sprintf('%s://%s:%s/', $nodeData->scheme, $nodeData->fqdn, $nodeData->daemonListen),
            'timeout' => 10.0,
            'connect_timeout' => 5.0,
        ]);

        return self::$guzzle[$node];

    }

}
