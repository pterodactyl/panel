<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sort_id
 * @property array $permissions
 */
class AdminRole extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'admin_role';

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
        'sort_id',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'sort_id' => 'int',
        'permissions' => 'array',
    ];

    public static array $validationRules = [
        'name' => 'required|string|max:64',
        'description' => 'nullable|string|max:255',
        'sort_id' => 'sometimes|numeric',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Gets the permissions associated with a admin role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
