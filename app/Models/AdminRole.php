<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sort_id
 */
class AdminRole extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'admin_role';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin_roles';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'name' => 'required|string|max:64',
        'description' => 'nullable|string|max:255',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;
}
