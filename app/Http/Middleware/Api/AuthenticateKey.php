<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateKey
{
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * AuthenticateKey constructor.
     */
    public function __construct(ApiKeyRepositoryInterface $repository, AuthManager $auth, Encrypter $encrypter)
    {
        $this->auth = $auth;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Handle an API request by verifying that the provided API key
     * is in a valid format and exists in the database.
     *
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Request $request, Closure $next, int $keyType)
    {
        if (is_null($request->bearerToken()) && is_null($request->user())) {
            throw new HttpException(401, null, null, ['WWW-Authenticate' => 'Bearer']);
        }

        $raw = $request->bearerToken();

        // This is a request coming through using cookies, we have an authenticated user not using
        // an API key. Make some fake API key models and continue on through the process.
        if (empty($raw) && $request->user() instanceof User) {
            $model = (new ApiKey())->forceFill([
                'user_id' => $request->user()->id,
                'key_type' => ApiKey::TYPE_ACCOUNT,
            ]);
        } else {
            $model = $this->authenticateApiKey($raw, $keyType);
            $this->auth->guard()->loginUsingId($model->user_id);
        }

        $request->attributes->set('api_key', $model);

        return $next($request);
    }

    /**
     * Authenticate an API key.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    protected function authenticateApiKey(string $key, int $keyType): ApiKey
    {
        $identifier = substr($key, 0, ApiKey::IDENTIFIER_LENGTH);
        $token = substr($key, ApiKey::IDENTIFIER_LENGTH);

        try {
            $model = $this->repository->findFirstWhere([
                ['identifier', '=', $identifier],
                ['key_type', '=', $keyType],
            ]);
        } catch (RecordNotFoundException $exception) {
            throw new AccessDeniedHttpException();
        }

        if (!hash_equals($this->encrypter->decrypt($model->token), $token)) {
            throw new AccessDeniedHttpException();
        }

        $this->repository->withoutFreshModel()->update($model->id, ['last_used_at' => CarbonImmutable::now()]);

        return $model;
    }
}
