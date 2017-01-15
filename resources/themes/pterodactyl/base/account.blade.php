{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

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

@section('title')
    {{ trans('base.account.header') }}
@endsection

@section('content-header')
    <h1>{{ trans('base.account.header') }}<small>{{ trans('base.account.header_sub')}}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">{{ trans('strings.home') }}</a></li>
        <li class="active">{{ trans('strings.account') }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('base.account.update_pass') }}</h3>
            </div>
            <form action="{{ route('account.password') }}" method="post">
                <div class="box-body">
                    <div class="form-group">
                        <label for="current_password" class="control-label">{{ trans('base.account.current_password') }}</label>
                        <div>
                            <input type="password" class="form-control" name="current_password" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_password" class="control-label">{{ trans('base.account.new_password') }}</label>
                        <div>
                            <input type="password" class="form-control" name="new_password" />
                            <p class="text-muted"><small>{{ trans('auth.password_requirements') }}</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_password_again" class="control-label">{{ trans('base.account.new_password_again') }}</label>
                        <div>
                            <input type="password" class="form-control" name="new_password_confirmation" />
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-primary btn-sm" value="{{ trans('base.account.update_pass') }}" />
                </div>
            </form>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('base.account.update_email') }}</h3>
            </div>
            <form action="{{ route('account.email') }}" method="post">
                <div class="box-body">
                    <div class="form-group">
                        <label for="new_email" class="control-label">{{ trans('base.account.new_email') }}</label>
                        <div>
                            <input type="text" class="form-control" name="new_email" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="control-label">{{ trans('base.account.current_password') }}</label>
                        <div>
                            <input type="password" class="form-control" name="password" />
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-primary btn-sm" value="{{ trans('base.account.update_email') }}" />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
