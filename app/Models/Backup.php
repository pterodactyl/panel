<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $uuid
 * @property string $name
 * @property string $contents
 * @property string $disk
 * @property string|null $sha256_hash
 * @property int $bytes
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 */
class Backup extends Model
{
    use SoftDeletes;

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
        'bytes' => 'int',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'completed_at',
    ];

    /**
     * Returns dates from this model as immutable Carbon instances.
     *
     * @param mixed $value
     * @return \Carbon\CarbonImmutable
     */
    protected function asDateTime($value)
    {
        return $this->asImmutableDateTime($value);
    }
}
