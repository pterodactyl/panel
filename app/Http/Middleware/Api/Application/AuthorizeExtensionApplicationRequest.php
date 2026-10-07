<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Application;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\TransientToken;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class AuthorizeExtensionApplicationRequest
{
    use ResolvesRequestContext;

    /**
     * Apply application API key permissions to extension routes. Core endpoints check
     * the key against the one resource they act on; extension endpoints have no resource
     * of their own, so the key must grant read access to every resource for GET, HEAD and
     * OPTIONS requests and write access to every resource for anything else. Session
     * requests and account keys pass, the same as in ApplicationApiRequest::authorize().
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->authenticatedUser($request)->currentAccessToken();
        if (! $token instanceof HasAbilities || $token instanceof TransientToken) {
            return $next($request);
        }

        if ($token->key_type === ApiKey::TYPE_ACCOUNT) {
            return $next($request);
        }

        $permission = in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) ? AdminAcl::READ : AdminAcl::WRITE;
        foreach (AdminAcl::getResourceList() as $resource) {
            throw_unless(AdminAcl::check($token, $resource, $permission), AccessDeniedHttpException::class, 'This API key does not have permission to access this resource.');
        }

        return $next($request);
    }
}
