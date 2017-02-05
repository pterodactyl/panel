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

class ServiceOptions extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_options';

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
         'service_id' => 'integer',
     ];

     /**
      * Gets service associated with a service option.
      *
      * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
      */
     public function service()
     {
         return $this->belongsTo(Service::class);
     }

     /**
      * Gets all servers associated with this service option.
      *
      * @return \Illuminate\Database\Eloquent\Relations\HasMany
      */
     public function servers()
     {
         return $this->hasMany(Server::class, 'option_id');
     }

     /**
      * Gets all variables associated with this service.
      *
      * @return \Illuminate\Database\Eloquent\Relations\HasMany
      */
     public function variables()
     {
         return $this->hasMany(ServiceVariables::class, 'option_id');
     }

     /**
      * Gets all packs associated with this service.
      *
      * @return \Illuminate\Database\Eloquent\Relations\HasMany
      */
     public function packs()
     {
         return $this->hasMany(ServicePack::class, 'option_id');
     }
}
