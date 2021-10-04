<?php

namespace Pterodactyl\Models;

use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * @property int $id
 * @property int $user_id
 * @property int $key_type
 * @property string $identifier
 * @property string $token
 * @property array $allowed_ips
 * @property string $memo
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ApiKey extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'api_key';

    /**
     * Different API keys that can exist on the system.
     */
    public const TYPE_NONE = 0;
    public const TYPE_ACCOUNT = 1;
    public const TYPE_APPLICATION = 2;
    public const TYPE_DAEMON_USER = 3;
    public const TYPE_DAEMON_APPLICATION = 4;

    /**
     * The length of API key identifiers.
     */
    public const IDENTIFIER_LENGTH = 16;

    /**
     * The length of the actual API key that is encrypted and stored
     * in the database.
     */
    public const KEY_LENGTH = 32;

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
        'allowed_ips' => 'array',
        'user_id' => 'int',
        'r_' . AdminAcl::RESOURCE_USERS => 'int',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'int',
        'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'int',
        'r_' . AdminAcl::RESOURCE_EGGS => 'int',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_NESTS => 'int',
        'r_' . AdminAcl::RESOURCE_NODES => 'int',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'int',
    ];

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identifier',
        'token',
        'allowed_ips',
        'memo',
        'last_used_at',
    ];

    /**
     * Fields that should not be included when calling toArray() or toJson()
     * on this model.
     *
     * @var array
     */
    protected $hidden = ['token'];

    /**
     * Rules to protect against invalid data entry to DB.
     *
     * @var array
     */
    public static $validationRules = [
        'user_id' => 'required|exists:users,id',
        'key_type' => 'present|integer|min:0|max:4',
        'identifier' => 'required|string|size:16|unique:api_keys,identifier',
        'token' => 'required|string',
        'memo' => 'required|nullable|string|max:500',
        'allowed_ips' => 'nullable|array',
        'allowed_ips.*' => 'string',
        'last_used_at' => 'nullable|date',
        'r_' . AdminAcl::RESOURCE_USERS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_EGGS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_NESTS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_NODES => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'integer|min:0|max:3',
    ];

    /**
     * @var array
     */
    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        'last_used_at',
    ];
}
