<?php

namespace Pterodactyl\Http\Requests\Api\Application;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Http\Middleware\Api\ApiSubstituteBindings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;

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

        return AdminAcl::check($this->key(), $this->resource, $this->permission);
    }

    /**
     * Determine if the requested resource exists on the server.
     */
    public function resourceExists(): bool
    {
        return true;
    }

    /**
     * Default set of rules to apply to API requests.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Return the API key being used for the request.
     */
    public function key(): ApiKey
    {
        return $this->attributes->get('api_key');
    }

    /**
     * Grab a model from the route parameters. If no model is found in the
     * binding mappings an exception will be thrown.
     *
     * @return mixed
     *
     * @deprecated
     *
     * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
     */
    public function getModel(string $model)
    {
        $parameterKey = array_get(array_flip(ApiSubstituteBindings::getMappings()), $model);

        if (is_null($parameterKey)) {
            throw new InvalidParameterException();
        }

        return $this->route()->parameter($parameterKey);
    }

    /**
     * Validate that the resource exists and can be accessed prior to booting
     * the validator and attempting to use the data.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function prepareForValidation()
    {
        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $this->hasValidated = true;
    }

    /*
     * Determine if the request passes the authorization check as well
     * as the exists check.
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function passesAuthorization()
    {
        // If we have already validated we do not need to call this function
        // again. This is needed to work around Laravel's normal auth validation
        // that occurs after validating the request params since we are doing auth
        // validation in the prepareForValidation() function.
        if ($this->hasValidated) {
            return true;
        }

        if (!parent::passesAuthorization()) {
            return false;
        }

        // Only let the user know that a resource does not exist if they are
        // authenticated to access the endpoint. This avoids exposing that
        // an item exists (or does not exist) to the user until they can prove
        // that they have permission to know about it.
        if ($this->attributes->get('is_missing_model', false) || !$this->resourceExists()) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        return true;
    }
}
