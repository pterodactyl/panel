<?php

namespace Pterodactyl\Tests\Traits;

use Mockery;
use Mockery\MockInterface;
use GuzzleHttp\Exception\RequestException;

trait MocksRequestException
{
    /**
     * @var \GuzzleHttp\Exception\RequestException|\Mockery\Mock
     */
    private $exception;

    /**
     * @var mixed
     */
    private $exceptionResponse;

    /**
     * Configure the exception mock to work with the Panel's default exception
     * handler actions.
     *
     * @param null $response
     */
    protected function configureExceptionMock(string $abstract = RequestException::class, $response = null)
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
