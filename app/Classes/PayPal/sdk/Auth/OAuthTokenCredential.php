<?php

namespace Pterodactyl\Classes\PayPal\sdk\Auth;

use Pterodactyl\Classes\PayPal\sdk\Cache\AuthorizationCache;
use Pterodactyl\Classes\PayPal\sdk\Common\PayPalResourceModel;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalHttpConfig;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalHttpConnection;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalLoggingManager;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConfigurationException;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConnectionException;
use Pterodactyl\Classes\PayPal\sdk\Handler\IPayPalHandler;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;
use Pterodactyl\Classes\PayPal\sdk\Security\Cipher;

/**
 * Class OAuthTokenCredential
 */
class OAuthTokenCredential extends PayPalResourceModel
{

    public static $CACHE_PATH = '/../../../var/auth.cache';

    /**
     * @var string Default Auth Handler
     */
    public static $AUTH_HANDLER = 'Pterodactyl\Classes\PayPal\sdk\Handler\OauthHandler';

    /**
     * Private Variable
     *
     * @var int $expiryBufferTime
     */
    private static $expiryBufferTime = 120;

    /**
     * Private Variable
     *
     * @var \Pterodactyl\Classes\PayPal\sdk\Core\PayPalLoggingManager $logger
     */
    private $logger;

    /**
     * Client ID as obtained from the developer portal
     *
     * @var string $clientId
     */
    private $clientId;

    /**
     * Client secret as obtained from the developer portal
     *
     * @var string $clientSecret
     */
    private $clientSecret;

    /**
     * Generated Access Token
     *
     * @var string $accessToken
     */
    private $accessToken;

    /**
     * Seconds for with access token is valid
     *
     * @var $tokenExpiresIn
     */
    private $tokenExpiresIn;

    /**
     * Last time (in milliseconds) when access token was generated
     *
     * @var $tokenCreateTime
     */
    private $tokenCreateTime;

    /**
     * Instance of cipher used to encrypt/decrypt data while storing in cache.
     *
     * @var Cipher
     */
    private $cipher;

    /**
     * Construct
     *
     * @param string $clientId     client id obtained from the developer portal
     * @param string $clientSecret client secret obtained from the developer portal
     */
    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cipher = new Cipher($this->clientSecret);
        $this->logger = PayPalLoggingManager::getInstance(__CLASS__);
    }

    /**
     * Get Client ID
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Get Client Secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Get AccessToken
     *
     * @param $config
     *
     * @return null|string
     */
    public function getAccessToken($config)
    {
        // Check if we already have accessToken in Cache
        if ($this->accessToken && (time() - $this->tokenCreateTime) < ($this->tokenExpiresIn - self::$expiryBufferTime)) {
            return $this->accessToken;
        }
        // Check for persisted data first
        $token = AuthorizationCache::pull($config, $this->clientId);
        if ($token) {
            // We found it
            // This code block is for backward compatibility only.
            if (array_key_exists('accessToken', $token)) {
                $this->accessToken = $token['accessToken'];
            }

            $this->tokenCreateTime = $token['tokenCreateTime'];
            $this->tokenExpiresIn = $token['tokenExpiresIn'];

            // Case where we have an old unencrypted cache file
            if (!array_key_exists('accessTokenEncrypted', $token)) {
                AuthorizationCache::push($config, $this->clientId, $this->encrypt($this->accessToken), $this->tokenCreateTime, $this->tokenExpiresIn);
            } else {
                $this->accessToken = $this->decrypt($token['accessTokenEncrypted']);
            }
        }

        // Check if Access Token is not null and has not expired.
        // The API returns expiry time as a relative time unit
        // We use a buffer time when checking for token expiry to account
        // for API call delays and any delay between the time the token is
        // retrieved and subsequently used
        if (
            $this->accessToken != null &&
            (time() - $this->tokenCreateTime) > ($this->tokenExpiresIn - self::$expiryBufferTime)
        ) {
            $this->accessToken = null;
        }


        // If accessToken is Null, obtain a new token
        if ($this->accessToken == null) {
            // Get a new one by making calls to API
            $this->updateAccessToken($config);
            AuthorizationCache::push($config, $this->clientId, $this->encrypt($this->accessToken), $this->tokenCreateTime, $this->tokenExpiresIn);
        }

        return $this->accessToken;
    }


    /**
     * Get a Refresh Token from Authorization Code
     *
     * @param $config
     * @param $authorizationCode
     * @param array $params optional arrays to override defaults
     * @return string|null
     */
    public function getRefreshToken($config, $authorizationCode = null, $params = array())
    {
        static $allowedParams = array(
            'grant_type' => 'authorization_code',
            'code' => 1,
            'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
            'response_type' => 'token'
        );

        $params = is_array($params) ? $params : array();
        if ($authorizationCode) {
            //Override the authorizationCode if value is explicitly set
            $params['code'] = $authorizationCode;
        }
        $payload = http_build_query(array_merge($allowedParams, array_intersect_key($params, $allowedParams)));

        $response = $this->getToken($config, $this->clientId, $this->clientSecret, $payload);

        if ($response != null && isset($response["refresh_token"])) {
            return $response['refresh_token'];
        }

        return null;
    }

    /**
     * Updates Access Token based on given input
     *
     * @param array $config
     * @param string|null $refreshToken
     * @return string
     */
    public function updateAccessToken($config, $refreshToken = null)
    {
        $this->generateAccessToken($config, $refreshToken);
        return $this->accessToken;
    }

    /**
     * Retrieves the token based on the input configuration
     *
     * @param array $config
     * @param string $clientId
     * @param string $clientSecret
     * @param string $payload
     * @return mixed
     * @throws PayPalConfigurationException
     * @throws \Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConnectionException
     */
    protected function getToken($config, $clientId, $clientSecret, $payload)
    {
        $httpConfig = new PayPalHttpConfig(null, 'POST', $config);

        $handlers = array(self::$AUTH_HANDLER);

        /** @var IPayPalHandler $handler */
        foreach ($handlers as $handler) {
            if (!is_object($handler)) {
                $fullHandler = "\\" . (string)$handler;
                $handler = new $fullHandler(new ApiContext($this));
            }
            $handler->handle($httpConfig, $payload, array('clientId' => $clientId, 'clientSecret' => $clientSecret));
        }

        $connection = new PayPalHttpConnection($httpConfig, $config);
        $res = $connection->execute($payload);
        $response = json_decode($res, true);

        return $response;
    }


    /**
     * Generates a new access token
     *
     * @param array $config
     * @param null|string $refreshToken
     * @return null
     * @throws PayPalConnectionException
     */
    private function generateAccessToken($config, $refreshToken = null)
    {
        $params = array('grant_type' => 'client_credentials');
        if ($refreshToken != null) {
            // If the refresh token is provided, it would get access token using refresh token
            // Used for Future Payments
            $params['grant_type'] = 'refresh_token';
            $params['refresh_token'] = $refreshToken;
        }
        $payload = http_build_query($params);
        $response = $this->getToken($config, $this->clientId, $this->clientSecret, $payload);

        if ($response == null || !isset($response["access_token"]) || !isset($response["expires_in"])) {
            $this->accessToken = null;
            $this->tokenExpiresIn = null;
            $this->logger->warning(
                "Could not generate new Access token. Invalid response from server: "
            );
            throw new PayPalConnectionException(null, "Could not generate new Access token. Invalid response from server: ");
        } else {
            $this->accessToken = $response["access_token"];
            $this->tokenExpiresIn = $response["expires_in"];
        }
        $this->tokenCreateTime = time();

        return $this->accessToken;
    }

    /**
     * Helper method to encrypt data using clientSecret as key
     *
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        return $this->cipher->encrypt($data);
    }

    /**
     * Helper method to decrypt data using clientSecret as key
     *
     * @param $data
     * @return string
     */
    public function decrypt($data)
    {
        return $this->cipher->decrypt($data);
    }
}
