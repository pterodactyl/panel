<?php

namespace Pterodactyl\Http\Controllers\API;

use JWTAuth;
use Hash;
use Validator;

use Tymon\JWTAuth\Exceptions\JWTException;

use Dingo\Api\Exception\StoreResourceFailedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use Pterodactyl\Transformers\UserTransformer;
use Pterodactyl\Models;

/**
 * @Resource("Auth", uri="/auth")
 */
class AuthController extends BaseController
{

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Lockout time for failed login requests.
     *
     * @var integer
     */
    protected $lockoutTime = 120;

    /**
     * After how many attempts should logins be throttled and locked.
     *
     * @var integer
     */
    protected $maxLoginAttempts = 3;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

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

        $validator = Validator::make($request->only(['email', 'password']), [
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('Required authentication fields were invalid.', $validator->errors());
        }

        $throttled = $this->isUsingThrottlesLoginsTrait();
        if ($throttled && $this->hasTooManyLoginAttempts($request)) {
            throw new TooManyRequestsHttpException('You have been login throttled for 120 seconds.');
        }

        // Is the email & password valid?
        $user = Models\User::where('email', $request->input('email'))->first();
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            if ($throttled) {
                $this->incrementLoginAttempts($request);
            }
            throw new UnauthorizedHttpException('A user by those credentials was not found.');
        }

        // @TODO: validate TOTP if enabled on account?
        // Perhaps this could be implemented in such a way that they login to their
        // account and generate a one time password that can be used? Would be a pain in
        // the butt for multiple API requests though. Maybe just included a 'totp' field
        // that can include the token for that timestamp. Would allow for programtic
        // generation of the code and API requests.
        if ($user->root_admin !== 1) {
            throw new UnauthorizedHttpException('This account does not have permission to interface this API.');
        }

        try {
            $token = JWTAuth::fromUser($user);
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
     * @Response(204)
     */
    public function postValidate(Request $request) {
        return $this->response->noContent();
    }

}
