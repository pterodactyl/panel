<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Services\Acl\Api\AdminAcl;
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
        'user_id' => 'int',
        'r_' . AdminAcl::RESOURCE_USERS => 'int',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_DATABASES => 'int',
        'r_' . AdminAcl::RESOURCE_EGGS => 'int',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_NESTS => 'int',
        'r_' . AdminAcl::RESOURCE_NODES => 'int',
        'r_' . AdminAcl::RESOURCE_PACKS => 'int',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'int',
    ];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'allowed_ips',
        'memo',
        'expires_at',
    ];

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
        'r_' . AdminAcl::RESOURCE_USERS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_DATABASES => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_EGGS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_NESTS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_NODES => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_PACKS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'integer|min:0|max:3',
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
     * Gets the permissions associated with a key.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(APIPermission::class, 'key_id');
    }
}
