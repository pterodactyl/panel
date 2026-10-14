<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;

/**
 * @property list<string> $ignored_files
 * @property-read Server $server
 */
#[Attributes\Identifiable('bkup')]
#[Guarded(['id', 'created_at', 'updated_at', 'deleted_at'])]
class Backup extends Model implements Identifiable
{
    /** @use HasFactory<BackupFactory> */
    use HasFactory;

    use HasRealtimeIdentifier;
    use SoftDeletes;

    public const string RESOURCE_NAME = 'backup';

    public const string ADAPTER_WINGS = 'wings';

    public const string ADAPTER_AWS_S3 = 's3';

    protected $attributes = [
        'is_successful' => false,
        'is_locked' => false,
        'checksum' => null,
        'bytes' => 0,
        'upload_id' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_successful' => 'boolean',
            'is_locked' => 'boolean',
            'ignored_files' => 'array',
            'bytes' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Backups still running or completed successfully.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function nonFailed(Builder $query): void
    {
        $query->where(fn (Builder $inner) => $inner->whereNull('completed_at')->orWhere('is_successful', true));
    }
}
