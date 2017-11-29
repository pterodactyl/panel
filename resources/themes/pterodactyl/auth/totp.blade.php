{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.auth')

@section('title')
    2FA Checkpoint
@endsection

@section('scripts')
    @parent
    <style>
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-offset-3 col-xs-offset-1 col-sm-6 col-xs-10 pterodactyl-login-box">
        <form id="totpForm" action="{{ route('auth.totp') }}" method="POST">
            <div class="form-group has-feedback">
                <div class="pterodactyl-login-input">
                    <input type="number" name="2fa_token" class="form-control input-lg" required placeholder="@lang('strings.2fa_token')" autofocus>
                    <span class="fa fa-shield form-control-feedback fa-lg"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-offset-8 col-xs-4">
                    {!! csrf_field() !!}
                    <input type="hidden" name="verify_token" value="{{ $verify_key }}" />
                    <button type="submit" class="btn btn-primary btn-block btn-flat pterodactyl-login-button--main">@lang('strings.submit')</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
