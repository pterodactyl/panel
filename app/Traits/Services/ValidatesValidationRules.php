<?php

namespace Pterodactyl\Traits\Services;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Pterodactyl\Exceptions\Service\Egg\Variable\BadValidationRuleException;

trait ValidatesValidationRules
{
    abstract protected function getValidator(): ValidationFactory;

    /**
     * Validate that the rules being provided are valid for Laravel and can
     * be resolved.
     *
     * @throws BadValidationRuleException
     */
    public function validateRules(array|string $rules): void
    {
        try {
            $this->getValidator()->make(['__TEST' => 'test'], ['__TEST' => $rules])->fails();
        } catch (\BadMethodCallException $exception) {
            $matches = [];
            if (preg_match('/Method \[(.+)\] does not exist\./', $exception->getMessage(), $matches)) {
                throw new BadValidationRuleException(trans('exceptions.nest.variables.bad_validation_rule', ['rule' => Str::snake(str_replace('validate', '', array_get($matches, 1, 'unknownRule')))]), $exception);
            }

            throw $exception;
        }
    }
}
