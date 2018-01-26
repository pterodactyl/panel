<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreServerDatabaseRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVER_DATABASES;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Validation rules for database creation.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'database' => 'required|string|min:1|max:24',
            'remote' => 'required|string|min:1',
            'host' => 'required|integer|exists:database_hosts,id',
        ];
    }

    /**
     * Return data formatted in the correct format for the service to consume.
     *
     * @return array
     */
    public function validated()
    {
        return [
            'database' => $this->input('database'),
            'remote' => $this->input('remote'),
            'database_host_id' => $this->input('host'),
        ];
    }

    /**
     * Format error messages in a more understandable format for API output.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'host' => 'Database Host Server ID',
            'remote' => 'Remote Connection String',
            'database' => 'Database Name',
        ];
    }
}
