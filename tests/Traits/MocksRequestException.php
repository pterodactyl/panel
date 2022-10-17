<?php

namespace Pterodactyl\Tests\Traits;

use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use GuzzleHttp\Exception\RequestException;

trait MocksRequestException
{
    private RequestException|Mock $exception;

    private mixed $exceptionResponse;

    /**
     * Configure the exception mock to work with the Panel's default exception
     * handler actions.
     */
    protected function configureExceptionMock(string $abstract = RequestException::class, $response = null): void
    {
        $this->getExceptionMock($abstract)->shouldReceive('getResponse')->andReturn(value($response));
    }

    /**
     * Return a mocked instance of the request exception.
     */
    protected function getExceptionMock(string $abstract = RequestException::class): MockInterface
    {
        return $this->exception ?? $this->exception = Mockery::mock($abstract);
    }
}
