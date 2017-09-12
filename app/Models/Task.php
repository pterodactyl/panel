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

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Task extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'server_id' => 'integer',
        'queued' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Default attributes when creating a new model.
     *
     * @var array
     */
    protected $attributes = [
        'parent_task_id' => null,
        'chain_order' => null,
        'active' => true,
        'day_of_week' => '*',
        'day_of_month' => '*',
        'hour' => '*',
        'minute' => '*',
        'chain_delay' => null,
        'queued' => false,
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'server_id' => 'required',
        'action' => 'required',
        'data' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'name' => 'nullable|string|max:255',
        'parent_task_id' => 'nullable|numeric|exists:tasks,id',
        'chain_order' => 'nullable|numeric|min:1',
        'server_id' => 'numeric|exists:servers,id',
        'active' => 'boolean',
        'action' => 'string',
        'data' => 'string',
        'queued' => 'boolean',
        'day_of_month' => 'string',
        'day_of_week' => 'string',
        'hour' => 'string',
        'minute' => 'string',
        'chain_delay' => 'nullable|numeric|between:1,900',
        'last_run' => 'nullable|timestamp',
        'next_run' => 'nullable|timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['last_run', 'next_run', 'created_at', 'updated_at'];

    /**
     * Return a hashid encoded string to represent the ID of the task.
     *
     * @return string
     */
    public function getHashidAttribute()
    {
        return app()->make('hashids')->encode($this->id);
    }

    /**
     * Gets the server associated with a task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the user associated with a task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Return chained tasks for a parent task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chained()
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('chain_order', 'asc');
    }
}
