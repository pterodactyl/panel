<?php

namespace Pterodactyl\Http\Middleware;

use Pterodactyl\Models\APIKey;
use Pterodactyl\Models\APIPermission;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Auth\Provider\Authorization;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; // 400
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException; // 401
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; // 403

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
                if (!in_array($request->ip(), json_decode($key->allowed_ips))) {
                    throw new AccessDeniedHttpException('This IP address does not have permission to use this API key.');
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

        if($this->_generateHMAC($request->fullUrl(), $request->getContent(), $key->secret) !== base64_decode($hashed)) {
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
