<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class APIKey extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    const KEY_LENGTH = 32;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_keys';

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'allowed_ips' => 'json',
    ];

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Rules defining what fields must be passed when making a model.
     *
     * @var array
     */
    protected static $applicationRules = [
        'memo' => 'required',
        'user_id' => 'required',
        'token' => 'required',
    ];

    /**
     * Rules to protect aganist invalid data entry to DB.
     *
     * @var array
     */
    protected static $dataIntegrityRules = [
        'user_id' => 'exists:users,id',
        'token' => 'string|size:32',
        'memo' => 'nullable|string|max:500',
        'allowed_ips' => 'nullable|json',
        'expires_at' => 'nullable|datetime',
    ];

    /**
     * Gets the permissions associated with a key.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(APIPermission::class, 'key_id');
    }
}
