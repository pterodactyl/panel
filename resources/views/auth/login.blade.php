@extends('layouts.master')

@section('title', 'Login')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('content')
<div class="col-md-8">
    <form action="/auth/login" method="POST" id="login-form">
        <legend>{{ trans('strings.login') }}</legend>
        <fieldset>
            <div class="form-group">
                <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                <div>
                    <input type="text" class="form-control" name="email" id="email" value="{{ old('email') }}" placeholder="{{ trans('strings.email') }}" />
                </div>
            </div>
            <div class="form-group">
                <label for="login-password" class="control-label">{{ trans('strings.password') }}</label>
                <div>
                    <input type="password" class="form-control" name="password" id="password" placeholder="{{ trans('strings.password') }}" />
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label><input type="checkbox" name="remember" /> {{ trans('auth.remeberme') }}</label>
                </div>
            </div>
            <div class="form-group">
                <div>
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-primary btn-sm" value="{{ trans('strings.login') }}" />
                    <button class="btn btn-default btn-sm" onclick="window.location='/auth/password';return false;">{{ trans('auth.resetpassword') }}</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>
<div class="modal fade" id="openTOTP" tabindex="-1" role="dialog">
    <div class="modal-dialog" style="width:400px;">
        <form action="/auth/login" method="POST" id="totp-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Two-Factor Authentication Checkpoint</h4>
                </div>
                <div class="modal-body" id="modal_insert_content">
                    <div class="form-group">
                        <label for="totp_token" class="control-label">Two-Factor Authentication Token</label>
                        <div>
                            <input class="form-control" type="text" placeholder="000111" name="totp_token" id="totp_token" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-default btn-sm" value="Continue" />
                </div>
            </div>
        </form>
    </div>
</div>
<div class="col-md-3"></div>
<script type="text/javascript">
$(document).ready(function() {
    $('#login-form').one('submit', function (event) {
        event.preventDefault();
        var check_email = $('#email').val();
        $.ajax({
            type: 'POST',
            url: '/auth/login/totp',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                email: check_email
            }
        }).done(function (data) {
            if (typeof data.id !== 'undefined') {
                $('#openTOTP').modal('show');
                $('#openTOTP').on('shown.bs.modal', function() {
                    $('#totp_token').focus();
                });
            } else {
                $('#login-form').submit();
            }
        }).fail(function (jqXHR) {
            alert('Unable to validate potential TOTP need.');
            console.error(jqXHR);
        });
    });
    $('#totp-form').submit(function () {
        return $('#login-form :input').not(':submit').clone().hide().appendTo('#totp-form');
    });
});
</script>
@endsection
