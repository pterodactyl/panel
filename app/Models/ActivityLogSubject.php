<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * \Pterodactyl\Models\ActivityLogSubject.
 *
 * @property int $id
 * @property int $activity_log_id
 * @property int $subject_id
 * @property string $subject_type
 * @property \Pterodactyl\Models\ActivityLog|null $activityLog
 * @property \Illuminate\Database\Eloquent\Model|\Eloquent $subject
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLogSubject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLogSubject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLogSubject query()
 *
 * @mixin \Eloquent
 */
class ActivityLogSubject extends Pivot
{
    public $incrementing = true;
    public $timestamps = false;

    protected $table = 'activity_log_subjects';

    protected $guarded = ['id'];

    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class);
    }

    public function subject()
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) {
            return $morph->withTrashed();
        }

        return $morph;
    }
}
