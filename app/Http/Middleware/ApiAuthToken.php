<?php

namespace App\Http\Middleware;

use App\Models\ApplicationApi;
use Closure;
use Illuminate\Http\Request;

class ApiAuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (empty($request->bearerToken())) {
            return response()->json(['message' => 'Missing Authorization header'], 403);
        }

        $token = ApplicationApi::find($request->bearerToken());
        if (is_null($token)) {
            return response()->json(['message' => 'Invalid Authorization token'], 401);
        }

        $token->updateLastUsed();

        return $next($request);
    }
}
