<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Facades\Alert;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DisplayException extends PterodactylException implements HttpExceptionInterface
{
    public const string LEVEL_DEBUG = 'debug';

    public const string LEVEL_INFO = 'info';

    public const string LEVEL_WARNING = 'warning';

    public const string LEVEL_ERROR = 'error';

    /**
     * DisplayException constructor.
     */
    public function __construct(string $message, ?Throwable $previous = null, protected string $level = self::LEVEL_ERROR, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorLevel(): string
    {
        return $this->level;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Render the exception to the user by adding a flashed message to the session
     * and then redirecting them back to the page that they came from. If the
     * request originated from an API hit, return the error in JSONAPI spec format.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(ApiErrorResponse::toArray($this), $this->getStatusCode(), $this->getHeaders());
        }

        Alert::danger($this->getMessage())->flash();

        return back()->withInput();
    }

    /**
     * Log the exception to the logs using the defined error level only if the previous
     * exception is set.
     */
    public function report(): void
    {
        $previous = $this->getPrevious();
        if (! $previous instanceof Throwable || ! Exceptions::shouldReport($previous)) {
            return;
        }

        $context = ['exception' => $previous];

        match ($this->getErrorLevel()) {
            self::LEVEL_DEBUG => Log::debug($previous->getMessage(), $context),
            self::LEVEL_INFO => Log::info($previous->getMessage(), $context),
            self::LEVEL_WARNING => Log::warning($previous->getMessage(), $context),
            default => Log::error($previous->getMessage(), $context),
        };
    }
}
