<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int $installed_by
 * @property string $name
 * @property string $directory
 * @property int $curseforge_project_id
 * @property int $curseforge_file_id
 * @property string $minecraft_version
 * @property bool $is_active
 * @property array|null $metadata
 */
class InstalledWorld extends Model
{
    protected $table = 'installed_worlds';

    protected $guarded = ['id', self::CREATED_AT, self::UPDATED_AT];

    protected $casts = [
        'server_id' => 'integer',
        'installed_by' => 'integer',
        'curseforge_project_id' => 'integer',
        'curseforge_file_id' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}
