<?php

namespace Pterodactyl\Exceptions;

use Exception;
use Prologue\Alerts\Facades\Alert;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        DisplayException::class,
        DataValidationException::class,
        DisplayValidationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        RecordNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $exception)
    {
        if ($request->expectsJson() || $request->isJson() || $request->is(...config('pterodactyl.json_routes'))) {
            $exception = $this->prepareException($exception);

            if (config('app.debug') || $this->isHttpException($exception) || $exception instanceof DisplayException) {
                $displayError = $exception->getMessage();
            } else {
                $displayError = 'An unhandled exception was encountered with this request.';
            }

            $response = response()->json(
                [
                    'error' => $displayError,
                    'http_code' => (method_exists($exception, 'getStatusCode')) ? $exception->getStatusCode() : 500,
                    'trace' => (! config('app.debug')) ? null : $exception->getTrace(),
                ],
                $this->isHttpException($exception) ? $exception->getStatusCode() : 500,
                $this->isHttpException($exception) ? $exception->getHeaders() : [],
                JSON_UNESCAPED_SLASHES
            );

            parent::report($exception);
        } elseif ($exception instanceof DisplayException) {
            Alert::danger($exception->getMessage())->flash();

            return redirect()->back()->withInput();
        }

        return (isset($response)) ? $response : parent::render($request, $exception);
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
}
