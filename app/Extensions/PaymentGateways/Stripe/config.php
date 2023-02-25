<?php

namespace App\Extensions\PaymentGateways\Stripe;

function getConfig()
{
    return [
        "name" => "Stripe",
        "description" => "Stripe payment gateway",
        "RoutesIgnoreCsrf" => [
            "payment/StripeWebhooks",
        ],
        "enabled" => config('SETTINGS::PAYMENTS:STRIPE:SECRET') && config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET') || config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_TEST_SECRET') && config('SETTINGS::PAYMENTS:STRIPE:TEST_SECRET') && env("APP_ENV") === "local",
    ];
}
