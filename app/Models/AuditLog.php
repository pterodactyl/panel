<?php

namespace Pterodactyl\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Container\Container;

/**
 * @deprecated — this class will be dropped in a future version, use the activity log
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

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
     *
     * @deprecated
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
