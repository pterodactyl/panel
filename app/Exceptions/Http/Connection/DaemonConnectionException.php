<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Http\Connection;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\DisplayException;
use Throwable;

/** @method Throwable getPrevious() */
class DaemonConnectionException extends DisplayException
{
    private readonly int $statusCode;

    private readonly ?string $requestId;

    public function __construct(Throwable $previous, bool $useStatusCode = true, ?ClientResponse $response = null)
    {
        $response ??= $previous instanceof RequestException ? $previous->response : null;
        $this->requestId = $response?->header('X-Request-Id');
        $this->statusCode = $this->statusFor($response, $useStatusCode);
        $level = $this->statusCode >= 500 && $this->statusCode !== Response::HTTP_GATEWAY_TIMEOUT
            ? self::LEVEL_ERROR
            : self::LEVEL_WARNING;

        parent::__construct($this->messageFor($response), $previous, $level);
    }

    public function report(): void
    {
        Log::log($this->getErrorLevel(), $this->getPrevious()->getMessage(), [
            'request_id' => $this->requestId,
            'exception' => $this->getPrevious(),
        ]);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    private function statusFor(?ClientResponse $response, bool $useStatusCode): int
    {
        if (! $useStatusCode || ! $response instanceof ClientResponse) {
            return Response::HTTP_GATEWAY_TIMEOUT;
        }

        // A truncated or malformed successful response is still an upstream failure.
        return $response->status() < 400 ? Response::HTTP_BAD_GATEWAY : $response->status();
    }

    private function messageFor(?ClientResponse $response): string
    {
        if (! $response instanceof ClientResponse) {
            return 'Could not establish a connection to the machine running this server. Please try again.';
        }

        $message = sprintf('There was an error while communicating with the machine running this server. This error has been logged, please try again. (code: %s) (request_id: %s)', $response->status(), $this->requestId ?? '<nil>');
        if ($this->statusCode >= 500) {
            return $message;
        }

        $body = $response->json();
        $remoteError = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : $message;

        return sprintf('An error occurred on the remote host: %s. (request id: %s)', $remoteError, $this->requestId ?? '<nil>');
    }
}
