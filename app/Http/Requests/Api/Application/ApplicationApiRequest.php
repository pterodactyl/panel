<?php

namespace Pterodactyl\Http\Requests\Api\Application;

use Webmozart\Assert\Assert;
use Illuminate\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Exceptions\PterodactylException;

abstract class ApplicationApiRequest extends FormRequest
{
    /**
     * Tracks if the request has been validated internally or not to avoid
     * making duplicate validation calls.
     *
     * @var bool
     */
    private $hasValidated = false;

    /**
     * The resource that should be checked when performing the authorization
     * function for this request.
     *
     * @var string|null
     */
    protected $resource;

    /**
     * The permission level that a given API key should have for accessing
     * the defined $resource during the request cycle.
     *
     * @var int
     */
    protected $permission = AdminAcl::NONE;

    /**
     * Determine if the current user is authorized to perform
     * the requested action against the API.
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function authorize(): bool
    {
        if (is_null($this->resource)) {
            throw new PterodactylException('An ACL resource must be defined on API requests.');
        }

        return AdminAcl::check($this->attributes->get('api_key'), $this->resource, $this->permission);
    }

    /**
     * Default set of rules to apply to API requests.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Helper method allowing a developer to easily hook into this logic without having
     * to remember what the method name is called or where to use it. By default this is
     * a no-op.
     */
    public function withValidator(Validator $validator): void
    {
        // do nothing
    }

    /**
     * Returns the named route parameter and asserts that it is a real model that
     * exists in the database.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param class-string<T> $expect
     *
     * @return T
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpDocSignatureInspection
     */
    public function parameter(string $key, string $expect)
    {
        $value = $this->route()->parameter($key);

        Assert::isInstanceOf($value, $expect);
        Assert::isInstanceOf($value, Model::class);
        Assert::true($value->exists);

        /* @var T $value */
        return $value;
    }
}
