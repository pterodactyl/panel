<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Models;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Nicolaslopezj\Searchable\SearchableTrait;

class Node extends Model
{
    use Notifiable, SearchableTrait;

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
      * Cast values to correct type.
      *
      * @var array
      */
     protected $casts = [
         'public' => 'integer',
         'location_id' => 'integer',
         'memory' => 'integer',
         'disk' => 'integer',
         'daemonListen' => 'integer',
         'daemonSFTP' => 'integer',
     ];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'public', 'name', 'location_id',
        'fqdn', 'scheme', 'memory',
        'memory_overallocate', 'disk',
        'disk_overallocate', 'upload_size',
        'daemonSecret', 'daemonBase',
        'daemonSFTP', 'daemonListen',
    ];

    protected $searchable = [
         'columns' => [
             'nodes.name' => 10,
             'nodes.fqdn' => 8,
             'locations.short' => 4,
             'locations.long' => 4,
         ],
         'joins' => [
            'locations' => ['locations.id', 'nodes.location_id'],
         ],
     ];

    /**
     * Return an instance of the Guzzle client for this specific node.
     *
     * @param array $headers
     * @return \GuzzleHttp\Client
     */
    public function guzzleClient($headers = [])
    {
        return new Client([
            'base_uri' => sprintf('%s://%s:%s/', $this->scheme, $this->fqdn, $this->daemonListen),
            'timeout' => env('GUZZLE_TIMEOUT', 5.0),
            'connect_timeout' => env('GUZZLE_CONNECT_TIMEOUT', 3.0),
            'headers' => $headers,
        ]);
    }

    /**
     * Returns the configuration in JSON format.
     *
     * @param bool $pretty Wether to pretty print the JSON or not
     * @return string The configration in JSON format
     */
    public function getConfigurationAsJson($pretty = false)
    {
        $config = [
            'web' => [
                'host' => '0.0.0.0',
                'listen' => $this->daemonListen,
                'ssl' => [
                    'enabled' => $this->scheme === 'https',
                    'certificate' => '/etc/letsencrypt/live/' . $this->fqdn . '/fullchain.pem',
                    'key' => '/etc/letsencrypt/live/' . $this->fqdn . '/privkey.pem',
                ],
            ],
            'docker' => [
                'socket' => '/var/run/docker.sock',
                'autoupdate_images' => true,
            ],
            'sftp' => [
                'path' => $this->daemonBase,
                'port' => $this->daemonSFTP,
                'container' => 'ptdl-sftp',
            ],
            'query' => [
                'kill_on_fail' => true,
                'fail_limit' => 5,
            ],
            'logger' => [
                'path' => 'logs/',
                'src' => false,
                'level' => 'info',
                'period' => '1d',
                'count' => 3,
            ],
            'remote' => [
                'base' => config('app.url'),
                'download' => route('remote.download'),
                'installed' => route('remote.install'),
            ],
            'uploads' => [
                'size_limit' => $this->upload_size,
            ],
            'keys' => [$this->daemonSecret],
        ];

        return json_encode($config, ($pretty) ? JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT : JSON_UNESCAPED_SLASHES);
    }

    /**
     * Gets the location associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gets the servers associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets the allocations associated with a node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }
}
