<?php

namespace Pterodactyl\Http\Requests\API\Admin;

use Pterodactyl\Models\APIKey;
use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class ApiAdminRequest extends FormRequest
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
    protected $permission = Acl::NONE;

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

        return Acl::check($this->key(), $this->resource, $this->permission);
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
     * @return \Pterodactyl\Models\APIKey
     */
    public function key(): APIKey
    {
        return $this->attributes->get('api_key');
    }

    /**
     * Determine if the request passes the authorization check as well
     * as the exists check.
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function passesAuthorization()
    {
        $passes = parent::passesAuthorization();

        // Only let the user know that a resource does not exist if they are
        // authenticated to access the endpoint. This avoids exposing that
        // an item exists (or does not exist) to the user until they can prove
        // that they have permission to know about it.
        if ($passes && ! $this->resourceExists()) {
            throw new NotFoundHttpException('The requested resource does not exist on this server.');
        }

        return $passes;
    }
}
