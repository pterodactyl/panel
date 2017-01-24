<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services;

use Log;
use Illuminate\Http\Request;
use Pterodactyl\Models\APILog;

class APILogService
{
    public function __constructor()
    {
        //
    }

    public static function log(Request $request, $error = null, $authorized = false)
    {
        if ($request->bearerToken() && ! empty($request->bearerToken())) {
            list($public, $hashed) = explode('.', $request->bearerToken());
        } else {
            $public = null;
        }

        try {
            $log = APILog::create([
                'authorized' => $authorized,
                'error' => $error,
                'key' => $public,
                'method' => $request->method(),
                'route' => $request->fullUrl(),
                'content' => (empty($request->getContent())) ? null : $request->getContent(),
                'user_agent' => $request->header('User-Agent'),
                'request_ip' => $request->ip(),
            ]);
            $log->save();
        } catch (\Exception $ex) {
            // Simply log it and move on.
            Log::error($ex);
        }
    }
}
