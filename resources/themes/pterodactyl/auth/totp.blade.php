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
<div class="login-box-body">
    <form action="{{ route('auth.totp') }}" method="POST">
        <div class="form-group has-feedback">
            <input type="number" name="2fa_token" class="form-control input-lg text-center" placeholder="@lang('strings.2fa_token')" autofocus>
            <span class="fa fa-shield form-control-feedback"></span>
        </div>
        <div class="row">
            <div class="col-xs-12">
                {!! csrf_field() !!}
                <input type="hidden" name="verify_token" value="{{ $verify_key }}" />
                @if($remember)
                    <input type="checkbox" name="remember" checked style="display:none;"/>
                @endif
                <button type="submit" class="btn btn-primary btn-block btn-flat">@lang('strings.submit')</button>
            </div>
        </div>
    </form>
</div>
@endsection
