<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\AccountLog;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Repositories\Eloquent\ApiKeyRepository;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\ApiKeyTransformer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreApiKeyRequest;

class ApiKeyController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService
     */
    private $keyCreationService;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ApiKeyRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Models\AccountLog
     */
    private $log;

    /**
     * ApiKeyController constructor.
     */
    public function __construct(
        Encrypter $encrypter,
        KeyCreationService $keyCreationService,
        ApiKeyRepository $repository,
        AccountLog $log,
    ) {
        parent::__construct();

        $this->encrypter = $encrypter;
        $this->keyCreationService = $keyCreationService;
        $this->repository = $repository;
        $this->log = $log;
    }

    /**
     * Returns all of the API keys that exist for the given client.
     *
     * @return array
     */
    public function index(ClientApiRequest $request)
    {
        return $this->fractal->collection($request->user()->apiKeys)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toArray();
    }

    /**
     * Store a new API key for a user's account.
     *
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function store(StoreApiKeyRequest $request)
    {
        if ($request->user()->apiKeys->count() >= 5) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        $token = $request->user()->createToken(
            $request->input('description'),
            $request->input('allowed_ips')
        );

        $this->log->create([
            'user_id' => $request->user()->id,
            'action' => 'API key \''.$request->input('description').'\' was created.',
            'ip_address' => $request->getClientIp(),
        ]);

        return $this->fractal->item($token->accessToken)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->addMeta(['secret_token' => $token->plainTextToken])
            ->toArray();
    }

    /**
     * Deletes a given API key.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(ClientApiRequest $request, string $identifier)
    {
        $response = $this->repository->deleteWhere([
            'key_type' => ApiKey::TYPE_ACCOUNT,
            'user_id' => $request->user()->id,
            'identifier' => $identifier,
        ]);

        $this->log->create([
            'user_id' => $request->user()->id,
            'action' => 'API key ('.$identifier.') was deleted.',
            'ip_address' => $request->getClientIp(),
        ]);

        if (!$response) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
