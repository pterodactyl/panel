{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com> --}}

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
@extends('layouts.master')

@section('title', 'Reset Password')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('content')
<div class="col-md-8">
    <form action="{{ url('/auth/password/reset') }}" method="POST">
        <legend>{{ trans('auth.resetpassword') }}</legend>
        <fieldset>
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                <div>
                    <input type="text" class="form-control" name="email" id="email" value="{{ $email or old('email') }}" required autofocus placeholder="{{ trans('strings.email') }}" />
                    @if ($errors->has('email'))
                        <span class="help-block">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
            </div>
            <div class="form-group">
                <label for="password" class="control-label">{{ trans('strings.password') }}</label>
                <div>
                    <input type="password" class="form-control" name="password" id="password" required placeholder="{{ trans('strings.password') }}" />
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="control-label">{{ trans('auth.confirmpassword') }}</label>
                <div>
                    <input type="password" class="form-control" id="password_confirmation" required name="password_confirmation" />
                    @if ($errors->has('password_confirmation'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password_confirmation') }}</strong>
                        </span>
                    @endif
                </div>
            </div>
            <div class="form-group">
                <div>
                    {!! csrf_field() !!}
                    <button class="btn btn-primary btn-sm">{{ trans('auth.resetpassword') }}</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>
<div class="col-md-3"></div>
@endsection
