{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.auth')

@section('title')
    Reset Password
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-offset-3 col-xs-offset-1 col-sm-6 col-xs-10">
            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    @lang('auth.auth_error')<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-sm-offset-3 col-xs-offset-1 col-sm-6 col-xs-10 pterodactyl-login-box">
            <form id="resetForm" action="{{ route('auth.reset.post') }}" method="POST">
                <div class="form-group has-feedback">
                    <div class="pterodactyl-login-input">
                        <input type="email" name="email" class="form-control input-lg" value="{{ $email ?? old('email') }}" required autofocus placeholder="@lang('strings.email')">
                        <span class="fa fa-envelope form-control-feedback fa-lg"></span>
                        @if ($errors->has('email'))
                            <span class="help-block text-red small">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="form-group has-feedback">
                    <div class="pterodactyl-login-input">
                        <input type="password" name="password" class="form-control input-lg" id="password" required placeholder="@lang('strings.password')">
                        <span class="fa fa-lock form-control-feedback fa-lg"></span>
                        @if ($errors->has('password'))
                            <span class="help-block text-red small">
                                {{ $errors->first('password') }}
                            </span>
                        @endif
                        <p class="small" style="color: #fff;">@lang('auth.password_requirements')</p>
                    </div>
                </div>
                <div class="form-group has-feedback">
                    <div class="pterodactyl-login-input">
                        <input type="password" name="password_confirmation" class="form-control input-lg" id="password_confirmation" required placeholder="@lang('strings.confirm_password')">
                        <span class="fa fa-lock form-control-feedback fa-lg"></span>
                        @if ($errors->has('password_confirmation'))
                            <span class="help-block text-red small">
                                {{ $errors->first('password_confirmation') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-4">
                        <a href="{{ route('auth.login') }}"><button type="button" class="btn pterodactyl-login-button--left"><i class="fa fa-user-circle"></i></button></a>
                    </div>
                    <div class="col-xs-offset-1 col-xs-7">
                        {!! csrf_field() !!}
                        <input type="hidden" name="token" value="{{ $token }}" />
                        <button type="submit" class="btn btn-block g-recaptcha pterodactyl-login-button--main" @if(config('recaptcha.enabled')) data-sitekey="{{ config('recaptcha.website_key') }}" data-callback='onSubmit' @endif>@lang('auth.reset_password')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    @if(config('recaptcha.enabled'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script>
        function onSubmit(token) {
            document.getElementById("resetForm").submit();
        }
        </script>
     @endif
@endsection
