<?php
/**
 * API handler for OAuth Token Request REST API calls
 */

namespace Pterodactyl\Classes\PayPal\sdk\Handler;

use Pterodactyl\Classes\PayPal\sdk\Common\PayPalUserAgent;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalConstants;
use Pterodactyl\Classes\PayPal\sdk\Core\PayPalHttpConfig;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConfigurationException;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalInvalidCredentialException;
use Pterodactyl\Classes\PayPal\sdk\Exception\PayPalMissingCredentialException;

/**
 * Class OauthHandler
 */
class OauthHandler implements IPayPalHandler
{
    /**
     * Private Variable
     *
     * @var \Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext $apiContext
     */
    private $apiContext;

    /**
     * Construct
     *
     * @param \Pterodactyl\Classes\PayPal\sdk\Rest\ApiContext $apiContext
     */
    public function __construct($apiContext)
    {
        $this->apiContext = $apiContext;
    }

    /**
     * @param PayPalHttpConfig $httpConfig
     * @param string                    $request
     * @param mixed                     $options
     * @return mixed|void
     * @throws PayPalConfigurationException
     * @throws PayPalInvalidCredentialException
     * @throws PayPalMissingCredentialException
     */
    public function handle($httpConfig, $request, $options)
    {
        $config = $this->apiContext->getConfig();

        $httpConfig->setUrl(
            rtrim(trim($this->_getEndpoint($config)), '/') .
            (isset($options['path']) ? $options['path'] : '')
        );

        $headers = array(
            "User-Agent"    => PayPalUserAgent::getValue(PayPalConstants::SDK_NAME, PayPalConstants::SDK_VERSION),
            "Authorization" => "Basic " . base64_encode($options['clientId'] . ":" . $options['clientSecret']),
            "Accept"        => "*/*"
        );
        $httpConfig->setHeaders($headers);

        // Add any additional Headers that they may have provided
        $headers = $this->apiContext->getRequestHeaders();
        foreach ($headers as $key => $value) {
            $httpConfig->addHeader($key, $value);
        }
    }

    /**
     * Get HttpConfiguration object for OAuth API
     *
     * @param array $config
     *
     * @return PayPalHttpConfig
     * @throws \Pterodactyl\Classes\PayPal\sdk\Exception\PayPalConfigurationException
     */
    private static function _getEndpoint($config)
    {
        if (isset($config['oauth.EndPoint'])) {
            $baseEndpoint = $config['oauth.EndPoint'];
        } else if (isset($config['service.EndPoint'])) {
            $baseEndpoint = $config['service.EndPoint'];
        } else if (isset($config['mode'])) {
            switch (strtoupper($config['mode'])) {
                case 'SANDBOX':
                    $baseEndpoint = PayPalConstants::REST_SANDBOX_ENDPOINT;
                    break;
                case 'LIVE':
                    $baseEndpoint = PayPalConstants::REST_LIVE_ENDPOINT;
                    break;
                default:
                    throw new PayPalConfigurationException('The mode config parameter must be set to either sandbox/live');
            }
        } else {
            // Defaulting to Sandbox
            $baseEndpoint = PayPalConstants::REST_SANDBOX_ENDPOINT;
        }

        $baseEndpoint = rtrim(trim($baseEndpoint), '/') . "/v1/oauth2/token";

        return $baseEndpoint;
    }
}
