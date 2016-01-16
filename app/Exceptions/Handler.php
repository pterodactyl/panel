<?php

namespace Pterodactyl\Exceptions;

use Exception;
use DisplayException;
use DisplayValidationException;
use AccountNotFoundException;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Validation\ValidationException;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        AuthorizationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        }

        if ($request->isXmlHttpRequest() || $request->ajax() || $request->is('remote/*')) {

            $exception = 'An exception occured while attempting to perform this action, please try again.';

            if ($e instanceof DisplayException) {
                $exception = $e->getMessage();
            }

            // Live environment, just return a nice error.
            if(!env('APP_DEBUG', false)) {
                return response()->json([
                    'error' => $exception
                ], 500);
            }

            // If we are debugging, return the exception in it's full manner.
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);

        }

        return parent::render($request, $e);
    }
}
