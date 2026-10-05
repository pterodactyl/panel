<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

abstract class ApiRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 50;

    public const int MAX_PER_PAGE = 100;

    /**
     * Default set of rules to apply to API requests.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [];
    }

    public function perPage(int $default = self::DEFAULT_PER_PAGE): int
    {
        $perPage = filter_var($this->validated('per_page') ?? $default, FILTER_VALIDATE_INT);
        throw_if($perPage === false, InvalidArgumentException::class, 'The validated per-page value must be an integer.');

        return $perPage;
    }

    /**
     * Returns the named route parameter and asserts that it is a real model that
     * exists in the database.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $expect
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function parameter(string $key, string $expect): Model
    {
        $route = $this->route();
        throw_if($route === null, InvalidArgumentException::class, 'Cannot resolve a route parameter without an active route.');

        $value = $route->parameter($key);

        throw_unless($value instanceof $expect, InvalidArgumentException::class, "Route parameter [$key] is not a valid model.");

        throw_unless($value->exists, InvalidArgumentException::class, "Route parameter [$key] does not exist.");

        return $value;
    }

    /** @return ValidationRules */
    protected function validationRules(): array
    {
        if (! $this->isMethod('GET')) {
            return $this->rules();
        }

        return array_merge($this->rules(), [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);
    }
}
