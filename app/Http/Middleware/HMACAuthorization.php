<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware;

use Auth;
use Crypt;
use Config;
use Closure;
use Debugbar;
use IPTools\IP;
use IPTools\Range;
use Illuminate\Http\Request;
use Pterodactyl\Models\APIKey;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; // 400
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; // 403

class HMACAuthorization
{
    /**
     * The algorithm to use for handling HMAC encryption.
     *
     * @var string
     */
    const HMAC_ALGORITHM = 'sha256';

    /**
     * Stored values from the Authorization header.
     *
     * @var array
     */
    protected $token = [];

    /**
     * The eloquent model for the API key.
     *
     * @var \Pterodactyl\Models\APIKey
     */
    protected $key;

    /**
     * The illuminate request object.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Construct class instance.
     */
    public function __construct()
    {
        Debugbar::disable();
        Config::set('session.driver', 'array');
    }

    /**
     * Handle an incoming request for the API.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->request = $request;

        $this->checkBearer();
        $this->validateRequest();
        $this->validateIPAccess();
        $this->validateContents();

        Auth::loginUsingId($this->key()->user_id);

        return $next($request);
    }

    /**
     * Checks that the Bearer token is provided and in a valid format.
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function checkBearer()
    {
        if (empty($this->request()->bearerToken())) {
            throw new BadRequestHttpException('Request was missing required Authorization header.');
        }

        $token = explode('.', $this->request()->bearerToken());
        if (count($token) !== 2) {
            throw new BadRequestHttpException('The Authorization header passed was not in a validate public/private key format.');
        }

        $this->token = [
            'public' => $token[0],
            'hash' => $token[1],
        ];
    }

    /**
     * Determine if the request contains a valid public API key
     * as well as permissions for the resource.
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function validateRequest()
    {
        $this->key = APIKey::where('public', $this->public())->first();
        if (! $this->key) {
            throw new AccessDeniedHttpException('Unable to identify requester. Authorization token is invalid.');
        }
    }

    /**
     * Determine if the requesting IP address is allowed to use this API key.
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function validateIPAccess()
    {
        if (! is_null($this->key()->allowed_ips)) {
            foreach (json_decode($this->key()->allowed_ips) as $ip) {
                if (Range::parse($ip)->contains(new IP($this->request()->ip()))) {
                    return true;
                }
            }

            throw new AccessDeniedHttpException('This IP address does not have permission to access the API using these credentials.');
        }

        return true;
    }

    /**
     * Determine if the HMAC sent in the request matches the one generated
     * on the panel side.
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    protected function validateContents()
    {
        if (! hash_equals(base64_decode($this->hash()), $this->generateSignature())) {
            throw new BadRequestHttpException('The HMAC for the request was invalid.');
        }
    }

    /**
     * Generate a HMAC from the request and known API secret key.
     *
     * @return string
     */
    protected function generateSignature()
    {
        $content = urldecode($this->request()->fullUrl()) . $this->request()->getContent();

        return hash_hmac(self::HMAC_ALGORITHM, $content, Crypt::decrypt($this->key()->secret), true);
    }

    /**
     * Return the public key passed in the Authorization header.
     *
     * @return string
     */
    protected function public()
    {
        return $this->token['public'];
    }

    /**
     * Return the base64'd HMAC sent in the Authorization header.
     *
     * @return string
     */
    protected function hash()
    {
        return $this->token['hash'];
    }

    /**
     * Return the API Key model.
     *
     * @return \Pterodactyl\Models\APIKey
     */
    protected function key()
    {
        return $this->key;
    }

    /**
     * Return the Illuminate Request object.
     *
     * @return \Illuminate\Http\Request
     */
    private function request()
    {
        return $this->request;
    }
}
