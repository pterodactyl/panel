<?php

namespace Pterodactyl\Exceptions\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\Validator;
use Pterodactyl\Exceptions\PterodactylException;
use Illuminate\Contracts\Support\MessageProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DataValidationException extends PterodactylException implements HttpExceptionInterface, MessageProvider
{
    /**
     * The validator instance.
     */
    protected Validator $validator;

    /**
     * The underlying model instance that triggered this exception.
     */
    protected Model $model;

    /**
     * DataValidationException constructor.
     */
    public function __construct(Validator $validator, Model $model)
    {
        $message = sprintf(
            'Could not save %s[%s]: failed to validate data: %s',
            get_class($model),
            $model->getKey(),
            $validator->errors()->toJson()
        );

        parent::__construct($message);

        $this->validator = $validator;
        $this->model = $model;
    }

    /**
     * Return the validator message bag.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this->validator->errors();
    }

    /**
     * Return the status code for this request.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return 500;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return [];
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}
