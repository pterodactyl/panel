<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * \Pterodactyl\Models\ActivityLogSubject.
 *
 * @property int $id
 * @property int $activity_log_id
 * @property int $subject_id
 * @property string $subject_type
 * @property ActivityLog|null $activityLog
 * @property Model $subject
 *
 * @method static Builder|ActivityLogSubject newModelQuery()
 * @method static Builder|ActivityLogSubject newQuery()
 * @method static Builder|ActivityLogSubject query()
 *
 * @mixin Model
 */
#[Guarded(['id'])]
#[Table(name: 'activity_log_subjects')]
#[WithoutTimestamps]
class ActivityLogSubject extends Pivot
{
    public $incrementing = true;

    /**
     * @return BelongsTo<ActivityLog, $this>
     */
    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class);
    }

    /**
     * @return MorphTo<Model, $this>
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
