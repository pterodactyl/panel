<?php

namespace Pterodactyl\Http\Controllers\API;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;
use \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use Pterodactyl\Transformers\UserTransformer;
use Pterodactyl\Models;

/**
 * @Resource("Auth", uri="/auth")
 */
class AuthController extends BaseController
{

    /**
     * Authenticate
     *
     * Authenticate with the API to recieved a JSON Web Token
     *
     * @Post("/login")
     * @Versions({"v1"})
     * @Request({"email": "e@mail.com", "password": "soopersecret"})
     * @Response(200, body={"token": "<jwt-token>"})
     */
    public function postLogin(Request $request) {
        $credentials = $request->only('email', 'password');

        try {
            $token = JWTAuth::attempt($credentials, [
                'permissions' => [
                    'view_users' => true,
                    'edit_users' => true,
                    'delete_users' => false,
                ]
            ]);
            if (!$token) {
                throw new UnauthorizedHttpException('');
            }
        } catch (JWTException $ex) {
            throw new ServiceUnavailableHttpException('');
        }

        return compact('token');
    }

    /**
     * Check if Authenticated
     *
     * @Post("/validate")
     * @Versions({"v1"})
     * @Request(headers={"Authorization": "Bearer <jwt-token>"})
     * @Response(204);
     */
    public function postValidate(Request $request) {
        return $this->response->noContent();
    }

}
