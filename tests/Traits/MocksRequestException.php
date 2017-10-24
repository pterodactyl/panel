<?php

namespace Tests\Traits;

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
     */
    public function configureExceptionMock()
    {
        $this->getExceptionMock()->shouldReceive('getResponse')->andReturn($this->exceptionResponse);
    }

    /**
     * Return a mocked instance of the request exception.
     *
     * @return \Mockery\MockInterface
     */
    private function getExceptionMock(): MockInterface
    {
        return $this->exception ?? $this->exception = Mockery::mock(RequestException::class);
    }

    /**
     * Set the exception response.
     *
     * @param mixed $response
     */
    protected function setExceptionResponse($response)
    {
        $this->exceptionResponse = $response;
    }
}
