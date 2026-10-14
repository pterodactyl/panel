<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Actions\Databases\CreateDatabaseHost;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\DatabaseHost;

class StoreDatabaseHostRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminDatabaseHostsCreate];
    }

    /**
     * Validation rules for creating or updating a database host.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateDatabaseHost::rules();
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return DatabaseHost::class;
    }
}
