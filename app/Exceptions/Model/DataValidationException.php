<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Exceptions\Model;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Support\MessageProvider;

class DataValidationException extends ValidationException implements MessageProvider
{
    /**
     * DataValidationException constructor.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    public function __construct(Validator $validator)
    {
        parent::__construct($validator);
    }

    /**
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this->validator->errors();
    }
}
