<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\ApiKeys;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Api\CreatesApiKeys;
use Pterodactyl\Contracts\Api\DeletesApiKeys;
use Pterodactyl\Contracts\Api\UpdatesApiKeys;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\ApiKeys\DeleteApiKeyRequest;
use Pterodactyl\Http\Requests\Api\Admin\ApiKeys\GetApiKeysRequest;
use Pterodactyl\Http\Requests\Api\Admin\ApiKeys\StoreApiKeyRequest;
use Pterodactyl\Http\Requests\Api\Admin\ApiKeys\UpdateApiKeyRequest;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Admin\ApiKeyTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('API Keys', 'Create and manage application API keys.')]
class ApiKeyController extends AdminApiController
{
    /**
     * List API keys.
     *
     * @return ApiPayload
     */
    #[Endpoint('List API keys', 'Returns a paginated list of application API keys.')]
    #[QueryParam('filter[memo]', 'string', 'Filter keys by memo.', required: false, example: 'Automation')]
    #[QueryParam('filter[identifier]', 'string', 'Filter keys by identifier.', required: false, example: 'ptla_1234567890abc')]
    #[QueryParam('sort', 'string', 'Sort keys by ID, memo, or creation date. Prefix with a hyphen for descending order.', required: false, example: '-created_at')]
    #[QueryParam('page', 'integer', 'Page number to retrieve.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[ResponseFromTransformer(ApiKeyTransformer::class, ApiKey::class, description: 'API keys returned.', collection: true, resourceKey: 'api_key', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetApiKeysRequest $request): array
    {
        $keys = QueryBuilder::for(ApiKey::query()->with('user')->where('key_type', ApiKey::TYPE_APPLICATION))
            ->allowedFilters(['memo', 'identifier'])
            ->allowedSorts(['id', 'memo', 'created_at'])
            ->paginate($request->perPage());

        return Fractal::collection($keys)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create API key.
     */
    #[Endpoint('Create API key', 'Creates a new application API key and returns the plaintext secret token once.')]
    #[ResponseFromTransformer(ApiKeyTransformer::class, ApiKey::class, status: 201, description: 'API key created.', resourceKey: 'api_key', meta: ['secret_token' => 'ptla_1234567890abcdef'])]
    public function store(StoreApiKeyRequest $request, CreatesApiKeys $keyCreator): JsonResponse
    {
        $key = $keyCreator->create(ApiKey::TYPE_APPLICATION, [
            'memo' => $request->string('memo')->toString(),
            'user_id' => $request->user()->id,
        ], $request->getKeyPermissions());
        $key->load('user');

        Activity::event('admin:api-key.create')
            ->subject($key)
            ->property('identifier', $key->identifier)
            ->log();

        return Fractal::item($key)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->addMeta([
                'secret_token' => $key->identifier.JsonValueGuard::string(Crypt::decrypt($key->token)),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Show API key.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get API key', 'Returns a single application API key by identifier without exposing its token.')]
    #[ResponseFromTransformer(ApiKeyTransformer::class, ApiKey::class, description: 'API key returned.', resourceKey: 'api_key')]
    public function show(GetApiKeysRequest $request, string $identifier): array
    {
        $key = ApiKey::query()
            ->with('user')
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('identifier', $identifier)
            ->firstOrFail();

        return Fractal::item($key)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toResponseArray();
    }

    /**
     * Update API key.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update API key', 'Updates an application API key memo and resource permissions without regenerating the token.')]
    #[ResponseFromTransformer(ApiKeyTransformer::class, ApiKey::class, description: 'API key updated.', resourceKey: 'api_key')]
    public function update(UpdateApiKeyRequest $request, UpdatesApiKeys $keyUpdater, string $identifier): array
    {
        $key = ApiKey::query()
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('identifier', $identifier)
            ->firstOrFail();

        $key = $keyUpdater->update($key, ['memo' => JsonValueGuard::nullableString($request->validated('memo'))], $request->getKeyPermissions());
        $key->load('user');

        Activity::event('admin:api-key.update')
            ->subject($key)
            ->property('identifier', $key->identifier)
            ->log();

        return Fractal::item($key)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete API key.
     */
    #[Endpoint('Delete API key', 'Deletes an application API key by identifier.')]
    #[ScribeResponse(status: 204, description: 'API key deleted.')]
    public function destroy(DeleteApiKeyRequest $request, DeletesApiKeys $keyDeleter, string $identifier): Response
    {
        $key = ApiKey::query()
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('identifier', $identifier)
            ->firstOrFail();

        Activity::event('admin:api-key.delete')
            ->subject($key)
            ->property('identifier', $key->identifier)
            ->log();

        $keyDeleter->delete($key);

        return $this->returnNoContent();
    }
}
