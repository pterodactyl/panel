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

class Service extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'author', 'description', 'folder', 'startup', 'index_file'];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'author' => 'required',
        'name' => 'required',
        'description' => 'sometimes',
        'folder' => 'required',
        'startup' => 'sometimes',
        'index_file' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'author' => 'string|size:36',
        'name' => 'string|max:255',
        'description' => 'nullable|string',
        'folder' => 'string|max:255|regex:/^[\w.-]{1,50}$/|unique:services,folder',
        'startup' => 'nullable|string',
        'index_file' => 'string',
    ];

    /**
     * Gets all service options associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(ServiceOption::class);
    }

    /**
     * Returns all of the packs associated with a service, regardless of the service option.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function packs()
    {
        return $this->hasManyThrough(
            Pack::class,
            ServiceOption::class,
            'service_id',
            'option_id'
        );
    }

    /**
     * Gets all servers associated with this service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
