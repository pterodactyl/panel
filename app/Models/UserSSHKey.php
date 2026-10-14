<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Database\Factories\UserSSHKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Larastan does not read #[Table], so it cannot find this model's columns.
 *
 * @property string $name
 * @property string $fingerprint
 * @property string $public_key
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'name',
    'public_key',
    'fingerprint',
])]
#[Table(name: 'user_ssh_keys')]
class UserSSHKey extends Model
{
    /** @use HasFactory<UserSSHKeyFactory> */
    use HasFactory;

    use SoftDeletes;

    public const string RESOURCE_NAME = 'ssh_key';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
