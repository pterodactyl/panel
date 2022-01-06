<?php

namespace Pterodactyl\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Container\Container;

/**
 * @property int $id
 * @property string $uuid
 * @property bool $is_system
 * @property int|null $user_id
 * @property int|null $server_id
 * @property string $action
 * @property string|null $subaction
 * @property array $device
 * @property array $metadata
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Pterodactyl\Models\User|null $user
 * @property \Pterodactyl\Models\Server|null $server
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const SERVER__BACKUP_STARTED = 'server:backup.started';
    public const SERVER__BACKUP_FAILED = 'server:backup.failed';
    public const SERVER__BACKUP_COMPELTED = 'server:backup.completed';
    public const SERVER__BACKUP_DELETED = 'server:backup.deleted';
    public const SERVER__BACKUP_DOWNLOADED = 'server:backup.downloaded';
    public const SERVER__BACKUP_LOCKED = 'server:backup.locked';
    public const SERVER__BACKUP_UNLOCKED = 'server:backup.unlocked';
    public const SERVER__BACKUP_RESTORE_STARTED = 'server:backup.restore.started';
    public const SERVER__BACKUP_RESTORE_COMPLETED = 'server:backup.restore.completed';
    public const SERVER__BACKUP_RESTORE_FAILED = 'server:backup.restore.failed';

    public const SERVER__DATABASE_CREATE = 'server:database.create';
    public const SERVER__DATABASE_PASSWORD_ROTATE = 'server:database.password.rotate';
    public const SERVER__DATABASE_DELETE = 'server:database.delete';

    public const SERVER__FILESYSTEM_DOWNLOAD = 'server:filesystem.download';
    public const SERVER__FILESYSTEM_WRITE = 'server:filesystem.write';
    public const SERVER__FILESYSTEM_DELETE = 'server:filesystem.delete';
    public const SERVER__FILESYSTEM_RENAME = 'server:filesystem.rename';
    public const SERVER__FILESYSTEM_COMPRESS = 'server:filesystem.compress';
    public const SERVER__FILESYSTEM_DECOMPRESS = 'server:filesystem.decompress';
    public const SERVER__FILESYSTEM_PULL = 'server:filesystem.pull';

    public const SERVER__ALLOCATION_SET_PRIMARY = 'server:allocation.set.primary';
    public const SERVER__ALLOCATION_DELETE = 'server:allocation.delete';
    public const SERVER__ALLOCATION_CREATE = 'server:allocation.create';

    public const SERVER__SCHEDULE_CREATE = 'server:schedule.create';
    public const SERVER__SCHEDULE_UPDATE = 'server:schedule.update';
    public const SERVER__SCHEDULE_DELETE = 'server:schedule.delete';
    public const SERVER__SCHEDULE_RUN = 'server:schedule.run';
    public const SERVER__SCHEDULE_TASK_CREATE = 'server:schedule.task.create';
    public const SERVER__SCHEDULE_TASK_UPDATE = 'server:schedule.task.update';
    public const SERVER__SCHEDULE_TASK_DELETE = 'server:schedule.task.delete';

    public const SERVER__SETTINGS_NAME = 'server:settings.name.update';
    public const SERVER__SETTINGS_REINSTALL = 'server:settings.reinstall';
    public const SERVER__SETTINGS_IMAGE = 'server:settings.image.update';

    public const SERVER__SUBUSER_CREATED = 'server:subuser.create';
    public const SERVER__SUBUSER_UPDATE = 'server:subuser.update';
    public const SERVER__SUBUSER_DELETE = 'server:subuser.delete';

    /**
     * @var string[]
     */
    public static $validationRules = [
        'uuid' => 'required|uuid',
        'action' => 'required|string|max:191',
        'subaction' => 'nullable|string|max:191',
        'device' => 'array',
        'device.ip_address' => 'ip',
        'device.user_agent' => 'string',
        'metadata' => 'array',
    ];

    /**
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * @var bool
     */
    protected $immutableDates = true;

    /**
     * @var string[]
     */
    protected $casts = [
        'is_system' => 'bool',
        'device' => 'array',
        'metadata' => 'array',
    ];

    /**
     * @var string[]
     */
    protected $guarded = [
        'id',
        'created_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Creates a new AuditLog model and returns it, attaching device information and the
     * currently authenticated user if available. This model is not saved at this point, so
     * you can always make modifications to it as needed before saving.
     *
     * @return $this
     */
    public static function instance(string $action, array $metadata, bool $isSystem = false)
    {
        /** @var \Illuminate\Http\Request $request */
        $request = Container::getInstance()->make('request');
        if ($isSystem || !$request instanceof Request) {
            $request = null;
        }

        return (new self())->fill([
            'uuid' => Uuid::uuid4()->toString(),
            'is_system' => $isSystem,
            'user_id' => ($request && $request->user()) ? $request->user()->id : null,
            'server_id' => null,
            'action' => $action,
            'device' => $request ? [
                'ip_address' => $request->getClientIp() ?? '127.0.0.1',
                'user_agent' => $request->userAgent() ?? '',
            ] : [],
            'metadata' => $metadata,
        ]);
    }
}