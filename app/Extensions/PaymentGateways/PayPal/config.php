<?php

namespace App\Extensions\PaymentGateways\PayPal;

function getConfig()
{
    return [
        "name" => "PayPal",
        "description" => "PayPal payment gateway",
        "RoutesIgnoreCsrf" => [],
        "enabled" => (config('SETTINGS::PAYMENTS:PAYPAL:SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID')) || (config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID') && env("APP_ENV") === "local"),
    ];
}
