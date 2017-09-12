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

use File;
use Storage;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Pack extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packs';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'option_id', 'uuid', 'name', 'version', 'description', 'selectable', 'visible', 'locked',
    ];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'name' => 'required',
        'version' => 'required',
        'description' => 'sometimes',
        'selectable' => 'sometimes|required',
        'visible' => 'sometimes|required',
        'locked' => 'sometimes|required',
        'option_id' => 'required',
    ];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [
        'name' => 'string',
        'version' => 'string',
        'description' => 'nullable|string',
        'selectable' => 'boolean',
        'visible' => 'boolean',
        'locked' => 'boolean',
        'option_id' => 'exists:service_options,id',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'option_id' => 'integer',
        'selectable' => 'boolean',
        'visible' => 'boolean',
        'locked' => 'boolean',
    ];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchableColumns = [
        'name' => 10,
        'uuid' => 8,
        'option.name' => 6,
        'option.tag' => 5,
        'option.docker_image' => 5,
        'version' => 2,
    ];

    /**
     * Returns all of the archived files for a given pack.
     *
     * @param bool $collection
     * @return \Illuminate\Support\Collection|object
     * @deprecated
     */
    public function files($collection = false)
    {
        $files = collect(Storage::files('packs/' . $this->uuid));

        $files = $files->map(function ($item) {
            $path = storage_path('app/' . $item);

            return (object) [
                'name' => basename($item),
                'hash' => sha1_file($path),
                'size' => File::humanReadableSize($path),
            ];
        });

        return ($collection) ? $files : (object) $files->all();
    }

    /**
     * Gets option associated with a service pack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function option()
    {
        return $this->belongsTo(ServiceOption::class);
    }

    /**
     * Gets servers associated with a pack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
