<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Validable extends Model
{
    /**
     * @var array
     */
    protected static $applicationRules = [];

    /**
     * @var array
     */
    protected static $dataIntegrityRules = [];

    /**
     * Listen for the model saving event and fire off the validation
     * function before it is saved.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Validable $model) {
            return $model->validate();
        });
    }

    /**
     * @todo implement custom logic once L6 is done
     * @return bool
     */
    public function validate()
    {
        return true;
    }
}
