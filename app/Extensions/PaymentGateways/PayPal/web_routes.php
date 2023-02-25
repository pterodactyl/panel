<?php

use Illuminate\Support\Facades\Route;

include_once(__DIR__ . '/index.php');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('payment/PayPalPay/{shopProduct}', function () {
        PaypalPay(request());
    })->name('payment.PayPalPay');

    Route::get(
        'payment/PayPalSuccess',
        function () {
            PaypalSuccess(request());
        }
    )->name('payment.PayPalSuccess');
});
