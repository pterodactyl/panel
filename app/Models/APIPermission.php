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

class APIPermission extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * List of permissions available for the API.
     */
    const CONST_PERMISSIONS = [
        // Items within this block are available to non-adminitrative users.
        '_user' => [
            'server' => [
                'list',
                'view',
                'power',
                'command',
            ],
        ],

        // All other pemissions below are administrative actions.
        'server' => [
            'list',
            'create',
            'view',
            'edit-details',
            'edit-container',
            'edit-build',
            'edit-startup',
            'suspend',
            'install',
            'rebuild',
            'delete',
        ],
        'location' => [
            'list',
        ],
        'node' => [
            'list',
            'view',
            'view-config',
            'create',
            'delete',
        ],
        'user' => [
            'list',
            'view',
            'create',
            'edit',
            'delete',
        ],
        'service' => [
            'list',
            'view',
        ],
        'option' => [
            'list',
            'view',
        ],
        'pack' => [
            'list',
            'view',
        ],
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_permissions';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'key_id' => 'integer',
    ];

    protected static $dataIntegrityRules = [
        'key_id' => 'required|numeric',
        'permission' => 'required|string|max:200',
    ];

    /**
     * Disable timestamps for this table.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Return permissions for API.
     *
     * @return array
     * @deprecated
     */
    public static function permissions()
    {
        return [];
    }
}
