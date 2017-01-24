{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.auth')

@section('title')
    Forgot Password
@endsection

@section('content')
<div class="login-box-body">
    @if (session('status'))
        <div class="callout callout-success">
            @lang('auth.email_sent')
        </div>
    @endif
    <p class="login-box-msg">@lang('auth.request_reset_text')</p>
    <form action="{{ route('auth.password') }}" method="POST">
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
                <button type="submit" class="btn btn-primary btn-block btn-flat">@lang('auth.request_reset')</button>
            </div>
        </div>
    </form>
</div>
@endsection
