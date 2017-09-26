<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class ServerVariable extends Model
{
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
        return $this->belongsTo(ServiceVariable::class, 'variable_id');
    }
}
