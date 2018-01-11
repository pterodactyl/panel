<?php
/*
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

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Znck\Eloquent\Traits\BelongsToThrough;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class DaemonKey extends Model implements CleansAttributes, ValidableContract
{
    use BelongsToThrough, Eloquence, Validable;

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
    protected static $applicationRules = [
        'user_id' => 'required',
        'server_id' => 'required',
        'secret' => 'required',
        'expires_at' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'user_id' => 'numeric|exists:users,id',
        'server_id' => 'numeric|exists:servers,id',
        'secret' => 'string|min:20',
        'expires_at' => 'date',
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
