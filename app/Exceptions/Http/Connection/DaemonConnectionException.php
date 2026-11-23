<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Http\Connection;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/** @method Throwable getPrevious() */
class DaemonConnectionException extends HttpException
{
    private readonly ?string $requestId;

    private readonly string $level;

    public function __construct(Throwable $previous, bool $useStatusCode = true, ?ClientResponse $response = null)
    {
        $response ??= $previous instanceof RequestException ? $previous->response : null;
        $this->requestId = $response?->header('X-Request-Id');
        $statusCode = $this->statusFor($response, $useStatusCode);
        $this->level = $statusCode >= 500 && $statusCode !== Response::HTTP_GATEWAY_TIMEOUT
            ? LogLevel::ERROR
            : LogLevel::WARNING;

        parent::__construct($statusCode, $this->messageFor($response, $statusCode), $previous);
    }

    /**
     * Log the failed Wings call. bootstrap/app.php keeps this exception reportable,
     * unlike other HTTP exceptions.
     */
    public function report(): void
    {
        Log::log($this->level, $this->getPrevious()->getMessage(), [
            'request_id' => $this->requestId,
            'exception' => $this->getPrevious(),
        ]);
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

    private function messageFor(?ClientResponse $response, int $statusCode): string
    {
        if (! $response instanceof ClientResponse) {
            return __('exceptions.daemon.unreachable');
        }

        $message = __('exceptions.daemon.communication_error', ['code' => $response->status(), 'request_id' => $this->requestId ?? '<nil>']);
        if ($statusCode >= 500) {
            return $message;
        }

        $body = $response->json();
        $remoteError = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : $message;

        return __('exceptions.daemon.remote_error', ['error' => $remoteError, 'request_id' => $this->requestId ?? '<nil>']);
    }
}
