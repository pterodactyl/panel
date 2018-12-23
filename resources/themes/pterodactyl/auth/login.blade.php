{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.auth')

@section('title')
    Login
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
        @foreach (Alert::getMessages() as $type => $messages)
            @foreach ($messages as $message)
                <div class="callout callout-{{ $type }} alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    {!! $message !!}
                </div>
            @endforeach
        @endforeach
    </div>
</div>
<div class="row">
    <div class="col-sm-offset-3 col-xs-offset-1 col-sm-6 col-xs-10 pterodactyl-login-box">
        <form id="loginForm" action="{{ route('auth.login') }}" method="POST">
            <div class="form-group has-feedback">
                <div class="pterodactyl-login-input">
                    <input type="text" name="user" class="form-control input-lg" value="{{ old('user') }}" required placeholder="@lang('strings.user_identifier')" autofocus>
                    <span class="fa fa-envelope form-control-feedback fa-lg"></span>
                </div>
            </div>
            <div class="form-group has-feedback">
                <div class="pterodactyl-login-input">
                    <input type="password" name="password" class="form-control input-lg" required placeholder="@lang('strings.password')">
                    <span class="fa fa-lock form-control-feedback fa-lg"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-4">
                    <a href="{{ route('auth.password') }}"><button type="button" class="btn pterodactyl-login-button--left"><i class="fa fa-life-ring"></i></button></a>
                </div>
                <div class="col-xs-offset-4 col-xs-4">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-block g-recaptcha pterodactyl-login-button--main" @if(config('recaptcha.enabled')) data-sitekey="{{ config('recaptcha.website_key') }}" data-callback='onSubmit' @endif>@lang('auth.sign_in')</button>
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
            document.getElementById("loginForm").submit();
        }
        </script>
     @endif
@endsection
