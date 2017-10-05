<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
