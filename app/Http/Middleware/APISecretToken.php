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

namespace Pterodactyl\Http\Middleware;

use Auth;
use Crypt;
use Config;
use IPTools\IP;
use IPTools\Range;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\APIKey;
use Pterodactyl\Models\APIPermission;
use Pterodactyl\Services\APILogService;
use Dingo\Api\Auth\Provider\Authorization;
use Symfony\Component\HttpKernel\Exception\HttpException; // 400
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; // 401
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; // 403
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException; //500

class APISecretToken extends Authorization
{
    protected $algo = 'sha256';

    protected $permissionAllowed = false;

    protected $url = '';

    public function __construct()
    {
        Config::set('session.driver', 'array');
    }

    public function getAuthorizationMethod()
    {
        return 'Authorization';
    }

    public function authenticate(Request $request, Route $route)
    {
        if (! $request->bearerToken() || empty($request->bearerToken())) {
            APILogService::log($request, 'The authentication header was missing or malformed.');
            throw new UnauthorizedHttpException('The authentication header was missing or malformed.');
        }

        list($public, $hashed) = explode('.', $request->bearerToken());

        $key = APIKey::where('public', $public)->first();
        if (! $key) {
            APILogService::log($request, 'Invalid API Key.');
            throw new AccessDeniedHttpException('Invalid API Key.');
        }

        // Check for Resource Permissions
        if (! empty($request->route()->getName())) {
            if (! is_null($key->allowed_ips)) {
                $inRange = false;
                foreach (json_decode($key->allowed_ips) as $ip) {
                    if (Range::parse($ip)->contains(new IP($request->ip()))) {
                        $inRange = true;
                        break;
                    }
                }
                if (! $inRange) {
                    APILogService::log($request, 'This IP address <' . $request->ip() . '> does not have permission to use this API key.');
                    throw new AccessDeniedHttpException('This IP address <' . $request->ip() . '> does not have permission to use this API key.');
                }
            }

            $permission = APIPermission::where('key_id', $key->id)->where('permission', $request->route()->getName());

            // Suport Wildcards
            if (starts_with($request->route()->getName(), 'api.user')) {
                $permission->orWhere('permission', 'api.user.*');
            } elseif (starts_with($request->route()->getName(), 'api.admin')) {
                $permission->orWhere('permission', 'api.admin.*');
            }

            if (! $permission->first()) {
                APILogService::log($request, 'You do not have permission to access this resource. This API Key requires the ' . $request->route()->getName() . ' permission node.');
                throw new AccessDeniedHttpException('You do not have permission to access this resource. This API Key requires the ' . $request->route()->getName() . ' permission node.');
            }
        }

        try {
            $decrypted = Crypt::decrypt($key->secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $ex) {
            APILogService::log($request, 'There was an error while attempting to check your secret key.');
            throw new HttpException('There was an error while attempting to check your secret key.');
        }

        $this->url = urldecode($request->fullUrl());
        if ($this->_generateHMAC($request->getContent(), $decrypted) !== base64_decode($hashed)) {
            APILogService::log($request, 'The hashed body was not valid. Potential modification of contents in route.');
            throw new BadRequestHttpException('The hashed body was not valid. Potential modification of contents in route.');
        }

        // Log the Route Access
        APILogService::log($request, null, true);

        return Auth::loginUsingId($key->user);
    }

    protected function _generateHMAC($body, $key)
    {
        $data = $this->url . $body;

        return hash_hmac($this->algo, $data, $key, true);
    }
}
