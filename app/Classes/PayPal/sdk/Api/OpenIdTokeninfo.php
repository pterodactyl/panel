<?php
namespace Pterodactyl\Classes\PayPal\sdk\Api;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Transport\PayPalRestCall;

/**
 * Class OpenIdTokeninfo
 *
 * Token grant resource
 *
 * @property string scope
 * @property string access_token
 * @property string refresh_token
 * @property string token_type
 * @property string id_token
 * @property int expires_in
 */
class OpenIdTokeninfo extends PayPalResourceModel
{

    /**
     * OPTIONAL, if identical to the scope requested by the client; otherwise, REQUIRED.
     *
     * @param string $scope
     * @return self
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * OPTIONAL, if identical to the scope requested by the client; otherwise, REQUIRED.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * The access token issued by the authorization server.
     *
     * @param string $access_token
     * @return self
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * The access token issued by the authorization server.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * The refresh token, which can be used to obtain new access tokens using the same authorization grant as described in OAuth2.0 RFC6749 in Section 6.
     *
     * @param string $refresh_token
     * @return self
     */
    public function setRefreshToken($refresh_token)
    {
        $this->refresh_token = $refresh_token;
        return $this;
    }

    /**
     * The refresh token, which can be used to obtain new access tokens using the same authorization grant as described in OAuth2.0 RFC6749 in Section 6.
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * The type of the token issued as described in OAuth2.0 RFC6749 (Section 7.1).  Value is case insensitive.
     *
     * @param string $token_type
     * @return self
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
        return $this;
    }

    /**
     * The type of the token issued as described in OAuth2.0 RFC6749 (Section 7.1).  Value is case insensitive.
     *
     * @return string
     */
    public function getTokenType()
    {
        return $this->token_type;
    }

    /**
     * The id_token is a session token assertion that denotes the user's authentication status
     *
     * @param string $id_token
     * @return self
     */
    public function setIdToken($id_token)
    {
        $this->id_token = $id_token;
        return $this;
    }

    /**
     * The id_token is a session token assertion that denotes the user's authentication status
     *
     * @return string
     */
    public function getIdToken()
    {
        return $this->id_token;
    }

    /**
     * The lifetime in seconds of the access token.
     *
     * @param integer $expires_in
     * @return self
     */
    public function setExpiresIn($expires_in)
    {
        $this->expires_in = $expires_in;
        return $this;
    }

    /**
     * The lifetime in seconds of the access token.
     *
     * @return integer
     */
    public function getExpiresIn()
    {
        return $this->expires_in;
    }


    /**
     * Creates an Access Token from an Authorization Code.
     *
     * @path /v1/identity/openidconnect/tokenservice
     * @method POST
     * @param array        $params     (allowed values are client_id, client_secret, grant_type, code and redirect_uri)
     *                                 (required) client_id from developer portal
     *                                 (required) client_secret from developer portal
     *                                 (required) code is Authorization code previously received from the authorization server
     *                                 (required) redirect_uri Redirection endpoint that must match the one provided during the
     *                                 authorization request that ended in receiving the authorization code.
     *                                 (optional) grant_type is the Token grant type. Defaults to authorization_code
     * @param string $clientId
     * @param string $clientSecret
     * @param ApiContext $apiContext Optional API Context
     * @param PayPalRestCall $restCall
     * @return OpenIdTokeninfo
     */
    public static function createFromAuthorizationCode($params, $clientId = null, $clientSecret = null, $apiContext = null, $restCall = null)
    {
        static $allowedParams = array('grant_type' => 1, 'code' => 1, 'redirect_uri' => 1);

        if (!array_key_exists('grant_type', $params)) {
            $params['grant_type'] = 'authorization_code';
        }
        $apiContext = $apiContext ? $apiContext : new ApiContext(self::$credential);

        if (sizeof($apiContext->get($clientId)) > 0) {
            $clientId = $apiContext->get($clientId);
        }

        if (sizeof($apiContext->get($clientSecret)) > 0) {
            $clientSecret = $apiContext->get($clientSecret);
        }

        $clientId = $clientId ? $clientId : $apiContext->getCredential()->getClientId();
        $clientSecret = $clientSecret ? $clientSecret : $apiContext->getCredential()->getClientSecret();

        $json = self::executeCall(
            "/v1/identity/openidconnect/tokenservice",
            "POST",
            http_build_query(array_intersect_key($params, $allowedParams)),
            array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($clientId . ":" . $clientSecret)
            ),
            $apiContext,
            $restCall
        );
        $token = new OpenIdTokeninfo();
        $token->fromJson($json);
        return $token;
    }

    /**
     * Creates an Access Token from an Refresh Token.
     *
     * @path /v1/identity/openidconnect/tokenservice
     * @method POST
     * @param array      $params     (allowed values are grant_type and scope)
     *                               (required) client_id from developer portal
     *                               (required) client_secret from developer portal
     *                               (optional) refresh_token refresh token. If one is not passed, refresh token from the current object is used.
     *                               (optional) grant_type is the Token grant type. Defaults to refresh_token
     *                               (optional) scope is an array that either the same or a subset of the scope passed to the authorization request
     * @param APIContext $apiContext Optional API Context
     * @return OpenIdTokeninfo
     */
    public function createFromRefreshToken($params, $apiContext = null)
    {
        static $allowedParams = array('grant_type' => 1, 'refresh_token' => 1, 'scope' => 1);
        $apiContext = $apiContext ? $apiContext : new ApiContext(self::$credential);

        if (!array_key_exists('grant_type', $params)) {
            $params['grant_type'] = 'refresh_token';
        }
        if (!array_key_exists('refresh_token', $params)) {
            $params['refresh_token'] = $this->getRefreshToken();
        }

        $clientId = isset($params['client_id']) ? $params['client_id'] : $apiContext->getCredential()->getClientId();
        $clientSecret = isset($params['client_secret']) ? $params['client_secret'] : $apiContext->getCredential()->getClientSecret();

        $json = self::executeCall(
            "/v1/identity/openidconnect/tokenservice",
            "POST",
            http_build_query(array_intersect_key($params, $allowedParams)),
            array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($clientId . ":" . $clientSecret)
            ),
            $apiContext
        );

        $this->fromJson($json);
        return $this;
    }
}
