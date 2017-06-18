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

use Watson\Validating\ValidatingTrait;
use Illuminate\Database\Eloquent\Model;

class DatabaseHost extends Model
{
    use ValidatingTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'database_hosts';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'host', 'port', 'username', 'max_databases', 'node_id',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'max_databases' => 'integer',
        'node_id' => 'integer',
    ];

    /**
     * Validation rules to assign to this model.
     *
     * @var array
     */
    protected $rules = [
        'name' => 'required|string|max:255',
        'host' => 'required|ip|unique:database_hosts,host',
        'port' => 'required|numeric|between:1,65535',
        'username' => 'required|string|max:32',
        'password' => 'sometimes|nullable|string',
        'node_id' => 'sometimes|required|nullable|exists:nodes,id',
    ];

    /**
     * Gets the node associated with a database host.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets the databases assocaited with this host.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }
}
