<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 */
class Mount extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'mount';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mounts';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
