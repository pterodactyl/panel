<?php

namespace Pterodactyl\Http\Middleware\Api\Daemon;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DaemonAuthenticate
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * Daemon routes that this middleware should be skipped on.
     *
     * @var array
     */
    protected $except = [
        'daemon.configuration',
    ];

    /**
     * DaemonAuthenticate constructor.
     */
    public function __construct(Encrypter $encrypter, NodeRepository $repository)
    {
        $this->repository = $repository;
        $this->encrypter = $encrypter;
    }

    /**
     * Check if a request from the daemon can be properly attributed back to a single node instance.
     *
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
            throw new HttpException(401, 'Access to this endpoint must include an Authorization header.', null, ['WWW-Authenticate' => 'Bearer']);
        }

        $parts = explode('.', $bearer);
        // Ensure that all of the correct parts are provided in the header.
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            throw new BadRequestHttpException('The Authorization header provided was not in a valid format.');
        }

        try {
            /** @var \Pterodactyl\Models\Node $node */
            $node = $this->repository->findFirstWhere([
                'daemon_token_id' => $parts[0],
            ]);

            if (hash_equals((string) $this->encrypter->decrypt($node->daemon_token), $parts[1])) {
                $request->attributes->set('node', $node);

                return $next($request);
            }
        } catch (RecordNotFoundException $exception) {
            // Do nothing, we don't want to expose a node not existing at all.
        }

        throw new AccessDeniedHttpException('You are not authorized to access this resource.');
    }
}
