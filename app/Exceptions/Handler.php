<?php

namespace Pterodactyl\Exceptions;

use Exception;
use PDOException;
use Psr\Log\LoggerInterface;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
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
     * A list of exceptions that should be logged with cleaned stack
     * traces to avoid exposing credentials or other sensitive information.
     *
     * @var array
     */
    protected $cleanStacks = [
        PDOException::class,
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
     * Report or log an exception. Skips Laravel's internal reporter since we
     * don't need or want the user information in our logs by default.
     *
     * If you want to implement logging in a different format to integrate with
     * services such as AWS Cloudwatch or other monitoring you can replace the
     * contents of this function with a call to the parent reporter.
     *
     * @param \Exception $exception
     * @return mixed
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        if (! config('app.exceptions.report_all', false) && $this->shouldntReport($exception)) {
            return null;
        }

        if (method_exists($exception, 'report')) {
            return $exception->report();
        }

        try {
            $logger = $this->container->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $exception;
        }

        foreach ($this->cleanStacks as $class) {
            if ($exception instanceof $class) {
                $exception = $this->generateCleanedExceptionStack($exception);
                break;
            }
        }

        return $logger->error($exception);
    }

    private function generateCleanedExceptionStack(Exception $exception)
    {
        $cleanedStack = '';
        foreach ($exception->getTrace() as $index => $item) {
            $cleanedStack .= sprintf(
                "#%d %s(%d): %s%s%s\n",
                $index,
                array_get($item, 'file'),
                array_get($item, 'line'),
                array_get($item, 'class'),
                array_get($item, 'type'),
                array_get($item, 'function')
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
     * @param \Exception               $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $exception)
    {
        $connections = Container::getInstance()->make(Connection::class);

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

        // Because of some breaking change snuck into a Laravel update that didn't get caught
        // by any of the tests, exceptions implementing the HttpExceptionInterface get marked
        // as being HttpExceptions, but aren't actually implementing the HttpException abstract.
        //
        // This is incredibly annoying because we can't just temporarily override the handler to
        // allow these (at least without taking on a high maintenance cost). Laravel 5.8 fixes this,
        // so when we update (or have updated) this code can be removed.
        //
        // @see https://github.com/laravel/framework/pull/25975
        // @todo remove this code when upgrading to Laravel 5.8
        if ($exception instanceof HttpExceptionInterface && ! $exception instanceof HttpException) {
            $exception = new HttpException(
                $exception->getStatusCode(),
                $exception->getMessage(),
                $exception,
                $exception->getHeaders(),
                $exception->getCode()
            );
        }

        return parent::render($request, $exception);
    }

    /**
     * Transform a validation exception into a consistent format to be returned for
     * calls to the API.
     *
     * @param \Illuminate\Http\Request                   $request
     * @param \Illuminate\Validation\ValidationException $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function invalidJson($request, ValidationException $exception)
    {
        $codes = collect($exception->validator->failed())->mapWithKeys(function ($reasons, $field) {
            $cleaned = [];
            foreach ($reasons as $reason => $attrs) {
                $cleaned[] = snake_case($reason);
            }

            return [str_replace('.', '_', $field) => $cleaned];
        })->toArray();

        $errors = collect($exception->errors())->map(function ($errors, $field) use ($codes) {
            $response = [];
            foreach ($errors as $key => $error) {
                $response[] = [
                    'code' => str_replace(self::PTERODACTYL_RULE_STRING, 'p_', array_get(
                        $codes, str_replace('.', '_', $field) . '.' . $key
                    )),
                    'detail' => $error,
                    'source' => ['field' => $field],
                ];
            }

            return $response;
        })->flatMap(function ($errors) {
            return $errors;
        })->toArray();

        return response()->json(['errors' => $errors], $exception->status);
    }

    /**
     * Return the exception as a JSONAPI representation for use on API requests.
     *
     * @param \Exception $exception
     * @param array      $override
     * @return array
     */
    public static function convertToArray(Exception $exception, array $override = []): array
    {
        $error = [
            'code' => class_basename($exception),
            'status' => method_exists($exception, 'getStatusCode') ? strval($exception->getStatusCode()) : '500',
            'detail' => 'An error was encountered while processing this request.',
        ];

        if (config('app.debug')) {
            $error = array_merge($error, [
                'detail' => $exception->getMessage(),
                'source' => [
                    'line' => $exception->getLine(),
                    'file' => str_replace(base_path(), '', $exception->getFile()),
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
     *
     * @param \Exception $exception
     * @return bool
     */
    public static function isReportable(Exception $exception): bool
    {
        return (new static(Container::getInstance()))->shouldReport($exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param \Illuminate\Http\Request                 $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('auth.login'));
    }

    /**
     * Converts an exception into an array to render in the response. Overrides
     * Laravel's built-in converter to output as a JSONAPI spec compliant object.
     *
     * @param \Exception $exception
     * @return array
     */
    protected function convertExceptionToArray(Exception $exception)
    {
        return self::convertToArray($exception);
    }
}
