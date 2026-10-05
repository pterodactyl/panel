<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureExtensionIsAvailable
{
    public function __construct(private ExtensionRepository $extensions) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $identifier): Response
    {
        abort_unless(config('extensions.enabled') && $this->extensions->enabled()->has($identifier), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
