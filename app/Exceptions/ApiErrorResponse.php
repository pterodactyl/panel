<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use UnexpectedValueException;

/**
 * Builds the JSON:API-style error envelope the panel's APIs return for any failure.
 */
final class ApiErrorResponse
{
    /**
     * The validation parser in Laravel formats custom rules using the class name
     * resulting in some weird rule names. This string will be parsed out and
     * replaced with 'p_' in the response code.
     */
    private const string PTERODACTYL_RULE_STRING = 'pterodactyl\_rules\_';

    /**
     * Render any exception as the error envelope with the status it implies.
     */
    public static function render(Throwable $e): JsonResponse
    {
        return new JsonResponse(
            self::toArray($e),
            self::status($e),
            $e instanceof HttpExceptionInterface ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Render a validation failure as one envelope entry per message, each carrying the
     * offending field and the rule that rejected it.
     */
    public static function validation(ValidationException $exception): JsonResponse
    {
        $codes = self::validationRuleCodes($exception);

        $errors = [];
        foreach ($exception->errors() as $field => $messages) {
            throw_if(! is_string($field) || ! is_array($messages), UnexpectedValueException::class, 'Laravel returned malformed validation errors.');

            foreach ($messages as $key => $message) {
                throw_if(! is_int($key) || ! is_string($message), UnexpectedValueException::class, 'Laravel returned a malformed validation error message.');

                $normalizedField = str_replace('.', '_', $field);
                $meta = [
                    'source_field' => $field,
                    'rule' => str_replace(self::PTERODACTYL_RULE_STRING, 'p_', $codes[$normalizedField][$key] ?? ''),
                ];

                $converted = self::toArray($exception)['errors'][0];
                $converted['detail'] = $message;
                $converted['meta'] = array_merge($converted['meta'] ?? [], $meta);

                $errors[] = $converted;
            }
        }

        return new JsonResponse(['errors' => $errors], $exception->status);
    }

    /**
     * The envelope for an exception; in debug mode it also carries the source
     * location and argument-free traces of the exception and everything before it.
     *
     * @param  ApiErrorOverride  $override
     * @return ApiErrorEnvelope
     */
    public static function toArray(Throwable $e, array $override = []): array
    {
        $error = [
            'code' => class_basename($e),
            'status' => (string) self::status($e),
            'detail' => $e instanceof HttpExceptionInterface || self::mappedStatus($e) !== null
                ? $e->getMessage()
                : 'An unexpected error was encountered while processing this request, please try again.',
        ];

        if ($e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException) {
            // Show a nicer error message compared to the standard "No query results for model"
            // response that is normally returned. If we are in debug mode this will get overwritten
            // with a more specific error message to help narrow down things.
            $error['detail'] = 'The requested resource could not be found on the server.';
        }

        if (config('app.debug')) {
            $error = array_merge($error, [
                'detail' => $e->getMessage(),
                'source' => [
                    'line' => $e->getLine(),
                    'file' => str_replace(base_path(), '', $e->getFile()),
                ],
                'meta' => [
                    'trace' => self::cleanTrace($e),
                    'previous' => array_map(self::cleanTrace(...), self::extractPrevious($e)),
                ],
            ]);
        }

        return ['errors' => [array_merge($error, $override)]];
    }

    private static function status(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return self::mappedStatus($e) ?? 500;
    }

    /**
     * Exceptions that carry no HTTP status of their own but map to a fixed one.
     */
    private static function mappedStatus(Throwable $e): ?int
    {
        return match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof ValidationException, $e instanceof InvalidExtensionException => 422,
            default => null,
        };
    }

    /**
     * Every exception that led to the given one being thrown, outermost first.
     *
     * @return list<Throwable>
     */
    private static function extractPrevious(Throwable $e): array
    {
        $previous = [];
        while ($value = $e->getPrevious()) {
            $previous[] = $value;
            $e = $value;
        }

        return $previous;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validationRuleCodes(ValidationException $exception): array
    {
        $codes = [];
        foreach ($exception->validator->failed() as $field => $reasons) {
            throw_if(! is_string($field) || ! is_array($reasons), UnexpectedValueException::class, 'Laravel returned malformed failed validation rules.');

            $cleaned = [];
            foreach (array_keys($reasons) as $reason) {
                throw_unless(is_string($reason), UnexpectedValueException::class, 'Laravel returned a malformed validation rule name.');

                $cleaned[] = Str::snake($reason);
            }

            $codes[str_replace('.', '_', $field)] = $cleaned;
        }

        return $codes;
    }

    /** @return list<array<string, ApiScalar>> */
    private static function cleanTrace(Throwable $exception): array
    {
        $cleaned = [];
        foreach ($exception->getTrace() as $frame) {
            $item = ['function' => $frame['function']];
            foreach (['file', 'line', 'class', 'type'] as $key) {
                if (isset($frame[$key])) {
                    $item[$key] = $frame[$key];
                }
            }

            $cleaned[] = $item;
        }

        return $cleaned;
    }
}
