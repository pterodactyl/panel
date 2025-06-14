<?php
namespace Pterodactyl\Classes\PayPal\sdk\Api;


use Pterodactyl\Classes\PayPal\sdk\Core\PayPalConstants;
use Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext;

class OpenIdSession
{

    /**
     * Returns the PayPal URL to which the user must be redirected to
     * start the authentication / authorization process.
     *
     * @param string $redirectUri Uri on merchant website to where
     *                                  the user must be redirected to post paypal login
     * @param array $scope The access privilges that you are requesting for
     *                                  from the user. Pass empty array for all scopes.
     * @param string $clientId client id from developer portal
     *                                  See https://developer.paypal.com/webapps/developer/docs/integration/direct/log-in-with-paypal/detailed/#attributes for more
     * @param null $nonce
     * @param null $state
     * @param ApiContext $apiContext Optional API Context
     * @return string Authorization URL
     */
    public static function getAuthorizationUrl($redirectUri, $scope, $clientId, $nonce = null, $state = null, $apiContext = null)
    {
        $apiContext = $apiContext ? $apiContext : new ApiContext();
        $config = $apiContext->getConfig();

        if ($apiContext->get($clientId)) {
            $clientId = $apiContext->get($clientId);
        }

        $clientId = $clientId ? $clientId : $apiContext->getCredential()->getClientId();

        $scope = count($scope) != 0 ? $scope : array('openid', 'profile', 'address', 'email', 'phone',
            'https://uri.paypal.com/services/paypalattributes', 'https://uri.paypal.com/services/expresscheckout');
        if (!in_array('openid', $scope)) {
            $scope[] = 'openid';
        }

        $params = array(
            'client_id' => $clientId,
            'response_type' => 'code',
            'scope' => implode(" ", $scope),
            'redirect_uri' => $redirectUri
        );

        if ($nonce) {
            $params['nonce'] = $nonce;
        }
        if ($state) {
            $params['state'] = $state;
        }
        return sprintf("%s/v1/authorize?%s", self::getBaseUrl($config), http_build_query($params));
    }


    /**
     * Returns the URL to which the user must be redirected to
     * logout from the OpenID provider (i.e. PayPal)
     *
     * @param string     $redirectUri   Uri on merchant website to where
     *                                  the user must be redirected to post logout
     * @param string     $idToken       id_token from the TokenInfo object
     * @param ApiContext $apiContext    Optional API Context
     * @return string logout URL
     */
    public static function getLogoutUrl($redirectUri, $idToken, $apiContext = null)
    {

        if (is_null($apiContext)) {
            $apiContext = new ApiContext();
        }
        $config = $apiContext->getConfig();

        $params = array(
            'id_token' => $idToken,
            'redirect_uri' => $redirectUri,
            'logout' => 'true'
        );
        return sprintf("%s/v1/endsession?%s", self::getBaseUrl($config), http_build_query($params));
    }

    /**
     * Gets the base URL for the Redirect URI
     *
     * @param $config
     * @return null|string
     */
    private static function getBaseUrl($config)
    {

        if (array_key_exists('openid.RedirectUri', $config)) {
            return $config['openid.RedirectUri'];
        } else if (array_key_exists('mode', $config)) {
            switch (strtoupper($config['mode'])) {
                case 'SANDBOX':
                    return PayPalConstants::OPENID_REDIRECT_SANDBOX_URL;
                case 'LIVE':
                    return PayPalConstants::OPENID_REDIRECT_LIVE_URL;
            }
        }
        return null;
    }
}
