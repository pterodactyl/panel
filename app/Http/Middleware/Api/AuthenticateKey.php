<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Lcobucci\JWT\Parser;
use Cake\Chronos\Chronos;
use Illuminate\Http\Request;
use Pterodactyl\Models\ApiKey;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Traits\Helpers\ProvidesJWTServices;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateKey
{
    use ProvidesJWTServices;

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
     *
     * @param \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface $repository
     * @param \Illuminate\Auth\AuthManager                                $auth
     * @param \Illuminate\Contracts\Encryption\Encrypter                  $encrypter
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
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param int                      $keyType
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Request $request, Closure $next, int $keyType)
    {
        if (is_null($request->bearerToken())) {
            throw new HttpException(401, null, null, ['WWW-Authenticate' => 'Bearer']);
        }

        $raw = $request->bearerToken();

        // This is an internal JWT, treat it differently to get the correct user before passing it along.
        if (strlen($raw) > ApiKey::IDENTIFIER_LENGTH + ApiKey::KEY_LENGTH) {
            $model = $this->authenticateJWT($raw);
        } else {
            $model = $this->authenticateApiKey($raw, $keyType);
        }

        $this->auth->guard()->loginUsingId($model->user_id);
        $request->attributes->set('api_key', $model);

        return $next($request);
    }

    /**
     * Authenticate an API request using a JWT rather than an API key.
     *
     * @param string $token
     * @return \Pterodactyl\Models\ApiKey
     */
    protected function authenticateJWT(string $token): ApiKey
    {
        $token = (new Parser)->parse($token);

        // If the key cannot be verified throw an exception to indicate that a bad
        // authorization header was provided.
        if (! $token->verify($this->getJWTSigner(), $this->getJWTSigningKey())) {
            throw new HttpException(401, null, null, ['WWW-Authenticate' => 'Bearer']);
        }

        // Run through the token validation and throw an exception if the token is not valid.
        if (
            $token->getClaim('nbf') > Chronos::now()->getTimestamp()
            || $token->getClaim('iss') !== 'Pterodactyl Panel'
            || $token->getClaim('aud') !== config('app.url')
            || $token->getClaim('exp') <= Chronos::now()->getTimestamp()
        ) {
            throw new AccessDeniedHttpException;
        }

        return (new ApiKey)->forceFill([
            'user_id' => object_get($token->getClaim('user'), 'id', 0),
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);
    }

    /**
     * Authenticate an API key.
     *
     * @param string $key
     * @param int    $keyType
     * @return \Pterodactyl\Models\ApiKey
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
            throw new AccessDeniedHttpException;
        }

        if (! hash_equals($this->encrypter->decrypt($model->token), $token)) {
            throw new AccessDeniedHttpException;
        }

        $this->repository->withoutFreshModel()->update($model->id, ['last_used_at' => Chronos::now()]);

        return $model;
    }
}
