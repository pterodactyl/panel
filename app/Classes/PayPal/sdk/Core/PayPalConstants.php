<?php

namespace Pterodactyl\Classes\PayPal\sdk\Core;

/**
 * Class PayPalConstants
 * Placeholder for Paypal Constants
 *
 * @package PayPal\Core
 */
class PayPalConstants
{

    const SDK_NAME = 'PayPal-PHP-SDK';
    const SDK_VERSION = '1.6.4';

    /**
     * Approval URL for Payment
     */
    const APPROVAL_URL = 'approval_url';

    const REST_SANDBOX_ENDPOINT = "https://api.sandbox.paypal.com/";
    const OPENID_REDIRECT_SANDBOX_URL = "https://www.sandbox.paypal.com/webapps/auth/protocol/openidconnect";

    const REST_LIVE_ENDPOINT = "https://api.paypal.com/";
    const OPENID_REDIRECT_LIVE_URL = "https://www.paypal.com/webapps/auth/protocol/openidconnect";
}
