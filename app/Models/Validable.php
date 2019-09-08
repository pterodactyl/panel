<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\Factory;

abstract class Validable extends Model
{
    /**
     * Determines if the model should undergo data validation before it is saved
     * to the database.
     *
     * @var bool
     */
    protected $skipValidation = false;

    /**
     * The validator instance used by this model.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected static $validatorFactory;

    /**
     * @var array
     */
    public static $validationRules = [];

    /**
     * Listen for the model saving event and fire off the validation
     * function before it is saved.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected static function boot()
    {
        parent::boot();

        static::$validatorFactory = Container::getInstance()->make(Factory::class);

        static::saving(function (Validable $model) {
            return $model->validate();
        });
    }

    /**
     * Set the model to skip validation when saving.
     *
     * @return $this
     */
    public function skipValidation()
    {
        $this->skipValidation = true;

        return $this;
    }

    /**
     * Returns the validator instance used by this model.
     *
     * @return \Illuminate\Validation\Validator|\Illuminate\Contracts\Validation\Validator
     */
    public function getValidator()
    {
        $rules = $this->getKey() ? static::getRulesForUpdate($this) : static::getRules();

        return $this->validator ?: $this->validator = static::$validatorFactory->make(
            [], $rules, [], []
        );
    }

    /**
     * Returns the rules associated with this model.
     *
     * @return array
     */
    public static function getRules()
    {
        $rules = static::$validationRules;
        foreach ($rules as $key => &$rule) {
            $rule = is_array($rule) ? $rule : explode('|', $rule);
        }

        return $rules;
    }

    /**
     * Returns the rules associated with the model, specifically for updating the given model
     * rather than just creating it.
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $id
     * @param string $primaryKey
     * @return array
     */
    public static function getRulesForUpdate($id, string $primaryKey = 'id')
    {
        if ($id instanceof Model) {
            [$primaryKey, $id] = [$id->getKeyName(), $id->getKey()];
        }

        $rules = static::getRules();
        foreach ($rules as $key => &$data) {
            // For each rule in a given field, iterate over it and confirm if the rule
            // is one for a unique field. If that is the case, append the ID of the current
            // working model so we don't run into errors due to the way that field validation
            // works.
            foreach ($data as &$datum) {
                if (! is_string($datum) || ! Str::startsWith($datum, 'unique')) {
                    continue;
                }

                [, $args] = explode(':', $datum);
                $args = explode(',', $args);

                $datum = Rule::unique($args[0], $args[1] ?? $key)->ignore($id, $primaryKey)->__toString();
            }
        }

        return $rules;
    }

    /**
     * Determines if the model is in a valid state or not.
     *
     * @return bool
     */
    public function validate()
    {
        if ($this->skipValidation) {
            return true;
        }

        return $this->getValidator()->setData(
            $this->getAttributes()
        )->passes();
    }
}
