<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $server_id
 * @property string $uuid
 * @property bool $is_successful
 * @property bool $is_locked
 * @property string $name
 * @property string[] $ignored_files
 * @property string $disk
 * @property string|null $checksum
 * @property int $bytes
 * @property string|null $upload_id
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Pterodactyl\Models\Server $server
 * @property \Pterodactyl\Models\AuditLog[] $audits
 */
class Backup extends Model
{
    use SoftDeletes;

    public const RESOURCE_NAME = 'backup';

    public const ADAPTER_WINGS = 'wings';
    public const ADAPTER_AWS_S3 = 's3';

    /**
     * @var string
     */
    protected $table = 'backups';

    /**
     * @var bool
     */
    protected $immutableDates = true;

    /**
     * @var array
     */
    protected $casts = [
        'id' => 'int',
        'is_successful' => 'bool',
        'is_locked' => 'bool',
        'ignored_files' => 'array',
        'bytes' => 'int',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'completed_at',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'is_successful' => false,
        'is_locked' => false,
        'checksum' => null,
        'bytes' => 0,
        'upload_id' => null,
    ];

    /**
     * @var string[]
     */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var array
     */
    public static $validationRules = [
        'server_id' => 'bail|required|numeric|exists:servers,id',
        'uuid' => 'required|uuid',
        'is_successful' => 'boolean',
        'is_locked' => 'boolean',
        'name' => 'required|string',
        'ignored_files' => 'array',
        'disk' => 'required|string',
        'checksum' => 'nullable|string',
        'bytes' => 'numeric',
        'upload_id' => 'nullable|string',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function audits()
    {
        return $this->hasMany(AuditLog::class, 'metadata->backup_uuid', 'uuid')
            ->where('action', 'LIKE', 'server:backup.%');
        // ->where('metadata->backup_uuid', $this->uuid);
    }
}
