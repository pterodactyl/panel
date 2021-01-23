<?php

namespace Pterodactyl\Exceptions;

use Exception;
use Throwable;
use PDOException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Swift_TransportException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * Laravel's validation parser formats custom rules using the class name
     * resulting in some weird rule names. This string will be parsed out and
     * replaced with 'p_' in the response code.
     */
    private const PTERODACTYL_RULE_STRING = 'pterodactyl\_rules\_';

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        RecordNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'token',
        'secret',
        'password',
        'password_confirmation',
    ];

    /**
     * Registers the exception handling callbacks for the application. This
     * will capture specific exception types that we do not want to include
     * the detailed stack traces for since they could reveal credentials to
     * whoever can read the logs.
     *
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function register()
    {
        if (config('app.exceptions.report_all', false)) {
            $this->dontReport = [];
        }

        $this->reportable(function (PDOException $ex) {
            $ex = $this->generateCleanedExceptionStack($ex);
        });

        $this->reportable(function (Swift_TransportException $ex) {
            $ex = $this->generateCleanedExceptionStack($ex);
        });
    }

    private function generateCleanedExceptionStack(Throwable $exception): string
    {
        $cleanedStack = '';
        foreach ($exception->getTrace() as $index => $item) {
            $cleanedStack .= sprintf(
                "#%d %s(%d): %s%s%s\n",
                $index,
                Arr::get($item, 'file'),
                Arr::get($item, 'line'),
                Arr::get($item, 'class'),
                Arr::get($item, 'type'),
                Arr::get($item, 'function')
            );
        }

        $message = sprintf(
            '%s: %s in %s:%d',
            class_basename($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        return $message . "\nStack trace:\n" . trim($cleanedStack);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $connections = $this->container->make(Connection::class);

        // If we are currently wrapped up inside a transaction, we will roll all the way
        // back to the beginning. This needs to happen, otherwise session data does not
        // get properly persisted.
        //
        // This is kind of a hack, and ideally things like this should be handled as
        // much as possible at the code level, but there are a lot of spots that do a
        // ton of actions and were written before this bug discovery was made.
        //
        // @see https://github.com/pterodactyl/panel/pull/1468
        if ($connections->transactionLevel()) {
            $connections->rollBack(0);
        }

        return parent::render($request, $exception);
    }

    /**
     * Transform a validation exception into a consistent format to be returned for
     * calls to the API.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function invalidJson($request, ValidationException $exception)
    {
        $codes = Collection::make($exception->validator->failed())->mapWithKeys(function ($reasons, $field) {
            $cleaned = [];
            foreach ($reasons as $reason => $attrs) {
                $cleaned[] = Str::snake($reason);
            }

            return [str_replace('.', '_', $field) => $cleaned];
        })->toArray();

        $errors = Collection::make($exception->errors())->map(function ($errors, $field) use ($codes, $exception) {
            $response = [];
            foreach ($errors as $key => $error) {
                $meta = [
                    'source_field' => $field,
                    'rule' => str_replace(self::PTERODACTYL_RULE_STRING, 'p_', Arr::get(
                        $codes,
                        str_replace('.', '_', $field) . '.' . $key
                    )),
                ];

                $converted = self::convertToArray($exception)['errors'][0];
                $converted['detail'] = $error;
                $converted['meta'] = is_array($converted['meta'] ?? null) ? array_merge($converted['meta'], $meta) : $meta;

                $response[] = $converted;
            }

            return $response;
        })->flatMap(function ($errors) {
            return $errors;
        })->toArray();

        return response()->json(['errors' => $errors], $exception->status);
    }

    /**
     * Return the exception as a JSONAPI representation for use on API requests.
     */
    public static function convertToArray(Throwable $exception, array $override = []): array
    {
        $error = [
            'code' => class_basename($exception),
            'status' => method_exists($exception, 'getStatusCode')
                ? strval($exception->getStatusCode())
                : ($exception instanceof ValidationException ? '422' : '500'),
            'detail' => $exception instanceof HttpExceptionInterface
                ? $exception->getMessage()
                : 'An unexpected error was encountered while processing this request, please try again.',
        ];

        if ($exception instanceof ModelNotFoundException || $exception->getPrevious() instanceof ModelNotFoundException) {
            // Show a nicer error message compared to the standard "No query results for model"
            // response that is normally returned. If we are in debug mode this will get overwritten
            // with a more specific error message to help narrow down things.
            $error['detail'] = 'The requested resource could not be found on the server.';
        }

        if (config('app.debug')) {
            $error = array_merge($error, [
                'detail' => $exception->getMessage(),
                'source' => [
                    'line' => $exception->getLine(),
                    'file' => str_replace(Application::getInstance()->basePath(), '', $exception->getFile()),
                ],
                'meta' => [
                    'trace' => explode("\n", $exception->getTraceAsString()),
                ],
            ]);
        }

        return ['errors' => [array_merge($error, $override)]];
    }

    /**
     * Return an array of exceptions that should not be reported.
     */
    public static function isReportable(Exception $exception): bool
    {
        return (new static(Container::getInstance()))->shouldReport($exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return new JsonResponse(self::convertToArray($exception), JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->container->make('redirect')->guest('/auth/login');
    }

    /**
     * Converts an exception into an array to render in the response. Overrides
     * Laravel's built-in converter to output as a JSONAPI spec compliant object.
     *
     * @return array
     */
    protected function convertExceptionToArray(Throwable $exception)
    {
        return self::convertToArray($exception);
    }
}
