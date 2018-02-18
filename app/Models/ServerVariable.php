<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class ServerVariable extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'server_variable';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'server_variables';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
         'server_id' => 'integer',
         'variable_id' => 'integer',
     ];

    /**
     * Determine if variable is viewable by users.
     *
     * @return bool
     */
    public function getUserCanViewAttribute()
    {
        return (bool) $this->variable->user_viewable;
    }

    /**
     * Determine if variable is editable by users.
     *
     * @return bool
     */
    public function getUserCanEditAttribute()
    {
        return (bool) $this->variable->user_editable;
    }

    /**
     * Determine if variable is required.
     *
     * @return bool
     */
    public function getRequiredAttribute()
    {
        return $this->variable->required;
    }

    /**
     * Returns information about a given variables parent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variable()
    {
        return $this->belongsTo(EggVariable::class, 'variable_id');
    }
}
