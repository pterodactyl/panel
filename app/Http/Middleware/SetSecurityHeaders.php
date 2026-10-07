<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Ideally we move away from X-Frame-Options/X-XSS-Protection and implement a
     * proper standard CSP, but I can guarantee that will break for a lot of folks
     * using custom plugins and who knows what image embeds.
     *
     * We'll circle back to that at a later date when it can be more fully controlled
     * by the admin to support those cases without too much trouble.
     *
     * @var array<string, string>
     */
    private static array $headers = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * Enforces some basic security headers on all responses returned by the software.
     * If a header has already been set in another location within the code it will be
     * skipped over here.
     *
     * @param  (Closure(mixed): Response)  $next
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        foreach (self::$headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
