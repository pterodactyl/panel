// Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

var TwoFactorModal = (function () {

    function bindListeners() {
        $(document).ready(function () {
            $('#close_reload').click(function () {
                location.reload();
            });
            $('#do_2fa').submit(function (event) {
                event.preventDefault();

                $.ajax({
                    type: 'PUT',
                    url: Router.route('account.security.totp'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                    }
                }).done(function (data) {
                    var image = new Image();
                    image.src = data.qrImage;
                    $(image).on('load', function () {
                        $('#hide_img_load').slideUp(function () {
                            $('#qr_image_insert').attr('src', image.src).slideDown();
                        });
                    });
                    $('#open2fa').modal('show');
                }).fail(function (jqXHR) {
                    alert('An error occured while attempting to load the 2FA setup modal. Please try again.');
                    console.error(jqXHR);
                });

            });
            $('#2fa_token_verify').submit(function (event) {
                event.preventDefault();
                $('#submit_action').html('<i class="fa fa-spinner fa-spin"></i> Submit').addClass('disabled');

                $.ajax({
                    type: 'POST',
                    url: Router.route('account.security.totp'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                    },
                    data: {
                        token: $('#2fa_token').val()
                    }
                }).done(function (data) {
                    $('#notice_box_2fa').hide();
                    if (data === 'true') {
                        $('#notice_box_2fa').html('<div class="alert alert-success">2-Factor Authentication has been enabled on your account. Press \'Close\' below to reload the page.</div>').slideDown();
                    } else {
                        $('#notice_box_2fa').html('<div class="alert alert-danger">The token provided was invalid.</div>').slideDown();
                    }
                }).fail(function (jqXHR) {
                    $('#notice_box_2fa').html('<div class="alert alert-danger">There was an error while attempting to enable 2-Factor Authentication on this account.</div>').slideDown();
                    console.error(jqXHR);
                }).always(function () {
                    $('#submit_action').html('Submit').removeClass('disabled');
                });
            });
        });
    }

    return {
        init: function () {
            bindListeners();
        }
    }
})();

TwoFactorModal.init();
