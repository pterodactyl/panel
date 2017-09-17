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
use Znck\Eloquent\Traits\BelongsToThrough;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Task extends Model implements CleansAttributes, ValidableContract
{
    use BelongsToThrough, Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * Relationships to be updated when this model is updated.
     *
     * @var array
     */
    protected $touches = ['schedule'];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'schedule_id',
        'sequence_id',
        'action',
        'payload',
        'time_offset',
        'is_queued',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'schedule_id' => 'integer',
        'sequence_id' => 'integer',
        'time_offset' => 'integer',
        'is_queued' => 'boolean',
    ];

    /**
     * Default attributes when creating a new model.
     *
     * @var array
     */
    protected $attributes = [
        'is_queued' => false,
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'schedule_id' => 'required',
        'sequence_id' => 'required',
        'action' => 'required',
        'payload' => 'required',
        'time_offset' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'schedule_id' => 'numeric|exists:schedules,id',
        'sequence_id' => 'numeric|min:1',
        'action' => 'string',
        'payload' => 'string',
        'time_offset' => 'numeric|between:0,900',
        'is_queued' => 'boolean',
    ];

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
     * Return the schedule that a task belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Return the server a task is assigned to, acts as a belongsToThrough.
     *
     * @return \Znck\Eloquent\Relations\BelongsToThrough
     * @throws \Exception
     */
    public function server()
    {
        return $this->belongsToThrough(Server::class, Schedule::class);
    }
}
