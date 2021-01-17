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
 * @property array $device
 * @property array $metadata
 * @property \Carbon\CarbonImmutable $created_at
 *
 * @property \Pterodactyl\Models\User|null $user
 * @property \Pterodactyl\Models\Server|null $server
 */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    const ACTION_USER_AUTH_LOGIN = 'user:auth.login';
    const ACTION_USER_AUTH_FAILED = 'user:auth.failed';
    const ACTION_USER_AUTH_PASSWORD_CHANGED = 'user:auth.password-changed';

    const ACTION_SERVER_BACKUP_RESTORE_STARTED = 'server:backup.restore.started';
    const ACTION_SERVER_BACKUP_RESTORE_COMPLETED = 'server:backup.restore.completed';
    const ACTION_SERVER_BACKUP_RESTORE_FAILED = 'server:backup.restore.failed';

    /**
     * @var string[]
     */
    public static $validationRules = [
        'uuid' => 'required|uuid',
        'action' => 'required|string',
        'device' => 'required|array',
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
     * @param string $action
     * @param array $metadata
     * @param bool $isSystem
     * @return $this
     */
    public static function factory(string $action, array $metadata, bool $isSystem = false)
    {
        /** @var \Illuminate\Http\Request $request */
        $request = Container::getInstance()->make('request');
        if (! $isSystem || ! $request instanceof Request) {
            $request = null;
        }

        return (new self())->fill([
            'uuid' => Uuid::uuid4()->toString(),
            'is_system' => $isSystem,
            'user_id' => $request->user() ? $request->user()->id : null,
            'server_id' => null,
            'action' => $action,
            'device' => $request ? [
                'ip_address' => $request->getClientIp(),
                'user_agent' => $request->userAgent(),
            ] : [],
            'metadata' => $metadata,
        ]);
    }
}
