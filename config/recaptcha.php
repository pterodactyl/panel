<?php

return [
    /*
     * Enable or disable captchas
     */
    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
     * API endpoint for recaptcha checks. You should not edit this.
     */
    'domain' => 'https://www.google.com/recaptcha/api/siteverify',

    /*
     * Use a custom secret key, we use our public one by default
     */
    'secret_key' => env('RECAPTCHA_SECRET_KEY', '6LekAxoUAAAAAPW-PxNWaCLH76WkClMLSa2jImwD'),

    /*
     * Use a custom website key, we use our public one by default
     */
    'website_key' => env('RECAPTCHA_WEBSITE_KEY', '6LekAxoUAAAAADjWZJ4ufcDRZBBiH9vfHawqRbup'),

    /*
     * Domain verification is enabled by default and compares the domain used when solving the captcha
     * as public keys can't have domain verification on google's side enabled (obviously).
     */
    'verify_domain' => true,
];
