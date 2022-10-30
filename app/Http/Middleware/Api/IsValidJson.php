<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use JsonException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IsValidJson
{
    /**
     * Throw an exception if the request should be valid JSON data but there is an error while
     * parsing the data. This avoids confusing validation errors where every field is flagged and
     * it is not immediately clear that there is an issue with the JSON being passed.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isJson() && !empty($request->getContent())) {
            try {
                json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new BadRequestHttpException('The JSON data passed in the request appears to be malformed: ' . $exception->getMessage());
            }
        }

        return $next($request);
    }
}
