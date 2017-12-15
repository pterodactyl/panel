<?php

namespace Pterodactyl\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Validable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Contracts\CleansAttributes;
use Sofa\Eloquence\Contracts\Validable as ValidableContract;

class Setting extends Model implements CleansAttributes, ValidableContract
{
    use Eloquence, Validable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['key', 'value'];

    /**
     * @var array
     */
    protected static $applicationRules = [
        'key' => 'required|string|between:1,255',
        'value' => 'string',
    ];
}
