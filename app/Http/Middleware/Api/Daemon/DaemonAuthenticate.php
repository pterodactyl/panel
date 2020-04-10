<?php

namespace Pterodactyl\Http\Middleware\Api\Daemon;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\Encrypter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DaemonAuthenticate
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * Daemon routes that this middleware should be skipped on.
     *
     * @var array
     */
    protected $except = [
        'daemon.configuration',
    ];

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * DaemonAuthenticate constructor.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(Encrypter $encrypter, NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->encrypter = $encrypter;
    }

    /**
     * Check if a request from the daemon can be properly attributed back to a single node instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (is_null($bearer = $request->bearerToken())) {
            throw new HttpException(
                401, 'Access this this endpoint must include an Authorization header.', null, ['WWW-Authenticate' => 'Bearer']
            );
        }

        [$identifier, $token] = explode('.', $bearer);

        try {
            /** @var \Pterodactyl\Models\Node $node */
            $node = $this->repository->findFirstWhere([
                'daemon_token_id' => $identifier,
            ]);

            if (hash_equals((string) $this->encrypter->decrypt($node->daemon_token), $token)) {
                $request->attributes->set('node', $node);

                return $next($request);
            }
        } catch (RecordNotFoundException $exception) {
            // Do nothing, we don't want to expose a node not existing at all.
        }

        throw new AccessDeniedHttpException(
            'You are not authorized to access this resource.'
        );
    }
}
