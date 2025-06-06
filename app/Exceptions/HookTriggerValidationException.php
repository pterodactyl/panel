<?php

namespace Pterodactyl\Exceptions;

class HookTriggerValidationException extends DisplayException
{
    protected array $errors;

    public function __construct(string $message, array $errors, \Throwable $previous = null)
    {
        parent::__construct($message, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
