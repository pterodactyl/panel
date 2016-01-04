@extends('layouts.master')

@section('title', 'Reset Password')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('content')
<div class="col-md-6">
    <form action="/auth/password/verify" method="POST">
        <legend>{{ trans('auth.resetpassword') }}</legend>
        <fieldset>
			<input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                <div>
                    <input type="text" class="form-control" name="email" id="email" value="{{ old('email') }}" placeholder="{{ trans('strings.email') }}" />
                </div>
            </div>
			<div class="form-group">
                <label for="password" class="control-label">{{ trans('strings.password') }}</label>
                <div>
                    <input type="password" class="form-control" name="password" id="password" placeholder="{{ trans('strings.password') }}" />
                </div>
            </div>
			<div class="form-group">
                <label for="password_confirmation" class="control-label">{{ trans('auth.confirmpassword') }}</label>
                <div>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" />
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
