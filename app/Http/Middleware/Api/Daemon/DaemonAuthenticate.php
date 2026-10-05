<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Daemon;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\Node;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DaemonAuthenticate
{
    use ResolvesRequestContext;

    /**
     * Daemon routes that this middleware should be skipped on.
     *
     * @var list<string>
     */
    protected array $except = [
        'daemon.configuration',
    ];

    /**
     * DaemonAuthenticate constructor.
     */
    public function __construct(private readonly Encrypter $encrypter) {}

    /**
     * Check if a request from the daemon can be properly attributed back to a single node instance.
     *
     * @throws HttpException
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($this->resolvedRoute($request)->getName(), $this->except, true)) {
            return $next($request);
        }

        throw_if(($bearer = $request->bearerToken()) === null, HttpException::class, 401, 'Access to this endpoint must include an Authorization header.', null, ['WWW-Authenticate' => 'Bearer']);

        $parts = explode('.', $bearer);
        // Ensure that all of the correct parts are provided in the header.
        throw_if(count($parts) !== 2 || empty($parts[0]) || empty($parts[1]), BadRequestHttpException::class, 'The Authorization header provided was not in a valid format.');

        // Unknown token ids and wrong tokens fail identically.
        $node = Node::query()->where('daemon_token_id', $parts[0])->first();
        if ($node !== null) {
            $token = $this->encrypter->decrypt($node->daemon_token);
            throw_unless(is_string($token), AccessDeniedHttpException::class, 'The authentication token provided is not valid.');

            if (hash_equals($token, $parts[1])) {
                $request->attributes->set('node', $node);

                return $next($request);
            }
        }

        throw new AccessDeniedHttpException('You are not authorized to access this resource.');
    }
}
