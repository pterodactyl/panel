<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Http\Middleware;

use Crypt;
use IPTools\IP;
use IPTools\Range;

use Pterodactyl\Models\APIKey;
use Pterodactyl\Models\APIPermission;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Auth\Provider\Authorization;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; // 400
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException; // 401
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; // 403
use Symfony\Component\HttpKernel\Exception\HttpException; //500

class APISecretToken extends Authorization
{

    protected $algo = 'sha256';

    protected $permissionAllowed = false;

    public function __construct()
    {
        //
    }

    public function getAuthorizationMethod()
    {
        return 'Authorization';
    }

    public function authenticate(Request $request, Route $route)
    {
        if (!$request->bearerToken() || empty($request->bearerToken())) {
            throw new UnauthorizedHttpException('The authentication header was missing or malformed');
        }

        list($public, $hashed) = explode('.', $request->bearerToken());

        $key = APIKey::where('public', $public)->first();
        if (!$key) {
            throw new AccessDeniedHttpException('Invalid API Key.');
        }

        // Check for Resource Permissions
        if (!empty($request->route()->getName())) {
            if(!is_null($key->allowed_ips)) {
                $inRange = false;
                foreach(json_decode($key->allowed_ips) as $ip) {
                    if (Range::parse($ip)->contains(new IP($request->ip()))) {
                        $inRange = true;
                        break;
                    }
                }
                if (!$inRange) {
                    throw new AccessDeniedHttpException('This IP address <' . $request->ip() . '> does not have permission to use this API key.');
                }
            }

            foreach(APIPermission::where('key_id', $key->id)->get() as &$row) {
                if ($row->permission === '*' || $row->permission === $request->route()->getName()) {
                    $this->permissionAllowed = true;
                    continue;
                }
            }

            if (!$this->permissionAllowed) {
                throw new AccessDeniedHttpException('You do not have permission to access this resource.');
            }
        }

        try {
            $decrypted = Crypt::decrypt($key->secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $ex) {
            throw new HttpException('There was an error while attempting to check your secret key.');
        }

        if($this->_generateHMAC($request->fullUrl(), $request->getContent(), $decrypted) !== base64_decode($hashed)) {
            throw new BadRequestHttpException('The hashed body was not valid. Potential modification of contents in route.');
        }

        return true;

    }

    protected function _generateHMAC($url, $body, $key)
    {
        $data = urldecode($url) . '.' . $body;
        return hash_hmac($this->algo, $data, $key, true);
    }

}
