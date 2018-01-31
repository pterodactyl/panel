<?php

namespace Pterodactyl\Http\Requests\Api\Application;

use Pterodactyl\Models\ApiKey;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Http\Middleware\Api\ApiSubstituteBindings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class ApplicationApiRequest extends FormRequest
{
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
     * the requested action aganist the API.
     *
     * @return bool
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
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        return true;
    }

    /**
     * Default set of rules to apply to API requests.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Return the API key being used for the request.
     *
     * @return \Pterodactyl\Models\ApiKey
     */
    public function key(): ApiKey
    {
        return $this->attributes->get('api_key');
    }

    /**
     * Grab a model from the route parameters. If no model exists under
     * the specified key a default response is returned.
     *
     * @param string $model
     * @param mixed  $default
     * @return mixed
     */
    public function getModel(string $model, $default = null)
    {
        $parameterKey = array_get(array_flip(ApiSubstituteBindings::getMappings()), $model);

        if (! is_null($parameterKey)) {
            $model = $this->route()->parameter($parameterKey);
        }

        return $model ?? $default;
    }

    /*
     * Determine if the request passes the authorization check as well
     * as the exists check.
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */

    /**
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (! parent::passesAuthorization()) {
            return false;
        }

        // Only let the user know that a resource does not exist if they are
        // authenticated to access the endpoint. This avoids exposing that
        // an item exists (or does not exist) to the user until they can prove
        // that they have permission to know about it.
        if ($this->attributes->get('is_missing_model', false) || ! $this->resourceExists()) {
            throw new NotFoundHttpException('The requested resource does not exist on this server.');
        }

        return true;
    }
}
