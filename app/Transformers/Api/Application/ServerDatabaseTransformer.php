<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use Illuminate\Support\Facades\Crypt;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;

#[ResponseField('max_connections', 'integer', example: 0, nullable: true)]
class ServerDatabaseTransformer extends BaseTransformer
{
    protected array $includeRelations = [
        'host' => ['relation' => 'host', 'transformer' => DatabaseHostTransformer::class, 'ability' => AdminAcl::RESOURCE_DATABASE_HOSTS],
    ];

    /**
     * @var list<string>
     */
    protected array $availableIncludes = ['password', 'host'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Database::RESOURCE_NAME;
    }

    /**
     * Transform a database model in a representation for the application API.
     *
     * @return ApiPayload
     */
    public function transform(Database $model): array
    {
        return [
            'id' => $model->id,
            'server' => $model->server_id,
            'host' => $model->database_host_id,
            'database' => $model->database,
            'username' => $model->username,
            'remote' => $model->remote,
            'max_connections' => $model->max_connections,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

    /**
     * Include the database password in the request.
     */
    public function includePassword(Database $model): Item
    {
        return $this->item($model, fn (Database $model): array => [
            'password' => Crypt::decrypt($model->password),
        ], 'database_password');
    }

    /**
     * Return the database host relationship for this server database.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeHost(Database $model): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_DATABASE_HOSTS)) {
            return $this->null();
        }

        $model->loadMissing('host');

        return $this->item(
            $model->getRelation('host'),
            $this->makeTransformer(DatabaseHostTransformer::class),
            DatabaseHost::RESOURCE_NAME
        );
    }
}
