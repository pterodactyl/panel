<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

Route::get('/logout', 'LoginController@logout')->name('auth.logout');
Route::get('/login', 'LoginController@showLoginForm')->name('auth.login');
Route::get('/login/totp', 'LoginController@totp')->name('auth.totp');
Route::get('/password', 'ForgotPasswordController@showLinkRequestForm')->name('auth.password');
Route::get('/password/reset/{token}', 'ForgotPasswordController@showResetForm')->name('auth.reset');

Route::post('/login', 'LoginController@login')->middleware('recaptcha');
Route::post('/login', 'LoginController@totpCheckpoint');
Route::post('/password/reset', 'ResetPasswordController@reset')->name('auth.reset.post')->middleware('recaptcha');
Route::post('/password/reset/{token}', 'ForgotPasswordController@sendResetLinkEmail')->middleware('recaptcha');
