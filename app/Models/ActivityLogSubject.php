<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * \Pterodactyl\Models\ActivityLogSubject.
 *
 * @property int $id
 * @property int $activity_log_id
 * @property int $subject_id
 * @property string $subject_type
 * @property ActivityLog|null $activityLog
 * @property \Illuminate\Database\Eloquent\Model $subject
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLogSubject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLogSubject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLogSubject query()
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ActivityLogSubject extends Pivot
{
    public $incrementing = true;
    public $timestamps = false;

    protected $table = 'activity_log_subjects';

    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Pterodactyl\Models\ActivityLog, $this>
     */
    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function subject(): MorphTo
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) { // @phpstan-ignore function.alreadyNarrowedType
            return $morph->withTrashed();
        }

        return $morph;
    }
}
