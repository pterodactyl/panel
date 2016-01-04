@extends('layouts.master')

@section('title', 'Reset Password')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('content')
<div class="col-md-6">
    <form action="/auth/password" method="POST">
        <legend>{{ trans('auth.resetpassword') }}</legend>
        <fieldset>
            @if (session('status'))
                <div class="alert alert-success">
                    <strong>{{ trans('strings.success') }}!</strong> {{ trans('auth.emailsent') }}
                </div>
            @endif
            <div class="form-group">
                <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                <div>
                    <input type="text" class="form-control" name="email" id="email" value="{{ old('email') }}" placeholder="{{ trans('strings.email') }}" />
                </div>
            </div>
            <div class="form-group">
                <div>
                    {!! csrf_field() !!}
                    <button class="btn btn-default btn-sm">{{ trans('auth.sendlink') }}</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>
<div class="col-md-3"></div>
@endsection
