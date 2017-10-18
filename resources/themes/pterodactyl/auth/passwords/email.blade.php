{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.auth')

@section('title')
    Forgot Password
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
    @if (session('status'))
        <div class="callout callout-success">
            @lang('auth.email_sent')
        </div>
    @endif
    <p class="login-box-msg">@lang('auth.request_reset_text')</p>
    <form id="resetForm" action="{{ route('auth.password') }}" method="POST">
        <div class="form-group has-feedback">
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" autofocus placeholder="@lang('strings.email')">
            <span class="fa fa-envelope form-control-feedback"></span>
            @if ($errors->has('email'))
                <span class="help-block text-red small">
                    {{ $errors->first('email') }}
                </span>
            @endif
        </div>
        <div class="row">
            <div class="col-xs-4">
                <a href="{{ route('auth.login') }}"><button type="button" class="btn btn-clear btn-block btn-flat">@lang('strings.login')</button></a>
            </div>
            <div class="col-xs-8">
                {!! csrf_field() !!}
                <button type="submit" class="btn btn-primary btn-block btn-flat g-recaptcha" @if(config('recaptcha.enabled')) data-sitekey="{{ config('recaptcha.website_key') }}" data-callback='onSubmit' @endif>@lang('auth.request_reset')</button>
            </div>
        </div>
    </form>
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
