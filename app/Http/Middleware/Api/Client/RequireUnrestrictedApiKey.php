<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RequireUnrestrictedApiKey
{
    /**
     * Blocks requests authenticated with a scoped API key from reaching account
     * level endpoints. Account endpoints can modify credentials that reach beyond
     * any key's scope — SSH keys grant SFTP access to every server the user owns,
     * and additional API keys could be created without restrictions — so scoped
     * keys are limited to server endpoints only.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $token = $request->user()?->currentApiKey();

        if (!is_null($token) && $token->isRestricted()) {
            throw new AccessDeniedHttpException('This API key is restricted and may not be used to access account endpoints.');
        }

        return $next($request);
    }
}
