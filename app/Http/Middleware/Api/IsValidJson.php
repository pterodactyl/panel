<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IsValidJson
{
    /**
     * Throw an exception if the request should be valid JSON data but there is an error while
     * parsing the data. This avoids confusing validation errors where every field is flagged and
     * it is not immediately clear that there is an issue with the JSON being passed.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isJson() && !empty($request->getContent())) {
            json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestHttpException(sprintf('The JSON data passed in the request appears to be malformed. err_code: %d err_message: "%s"', json_last_error(), json_last_error_msg()));
            }
        }

        return $next($request);
    }
}
