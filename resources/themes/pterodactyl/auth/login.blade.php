{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.auth')

@section('title')
    Login
@endsection

@section('content')
<div class="login-box-body">
    @if (count($errors) > 0)
        <div class="callout callout-danger">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            @lang('auth.auth_error')<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="callout callout-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {!! $message !!}
            </div>
        @endforeach
    @endforeach
    <p class="login-box-msg">@lang('auth.authentication_required')</p>
    <form id="loginForm" action="{{ route('auth.login') }}" method="POST">
        <div class="form-group has-feedback">
            <input name="user" class="form-control" value="{{ old('user') }}" placeholder="@lang('strings.user_identifier')">
            <span class="fa fa-envelope form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
            <input type="password" name="password" class="form-control" placeholder="@lang('strings.password')">
            <span class="fa fa-lock form-control-feedback"></span>
        </div>
        <div class="row">
            <div class="col-xs-8">
                <div class="form-group has-feedback">
                    <input type="checkbox" name="remember" id="remember" /> <label for="remember" class="weight-300">@lang('auth.remember_me')</label>
                </div>
            </div>
            <div class="col-xs-4">
                {!! csrf_field() !!}
                <button type="submit" class="btn btn-primary btn-block btn-flat g-recaptcha" @if(config('recaptcha.enabled')) data-sitekey="{{ config('recaptcha.website_key') }}" data-callback='onSubmit' @endif>@lang('auth.sign_in')</button>
            </div>
        </div>
    </form>
    <a href="{{ route('auth.password') }}">@lang('auth.forgot_password')</a><br>
</div>
@endsection

@section('scripts')
    @parent
    @if(config('recaptcha.enabled'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script>
        function onSubmit(token) {
            document.getElementById("loginForm").submit();
        }
        </script>
     @endif
@endsection
