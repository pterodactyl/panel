<?php

namespace Pterodactyl\Models;

class Setting extends Validable
{
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
