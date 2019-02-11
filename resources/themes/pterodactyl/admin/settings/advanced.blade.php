@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'advanced'])

@section('title')
    @lang('admin/settings.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/settings.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li class="active">@lang('admin/settings.header.settings')</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="" method="POST">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings.content.recaptcha')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings.content.status')</label>
                                <div>
                                    <select class="form-control" name="recaptcha:enabled">
                                        <option value="true">@lang('admin/settings.content.enabled')</option>
                                        <option value="false" @if(old('recaptcha:enabled', config('recaptcha.enabled')) == '0') selected @endif>@lang('admin/settings.content.disabled')</option>
                                    </select>
                                    <p class="text-muted small">@lang('admin/settings.content.toggle_hint')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings.content.site_key')</label>
                                <div>
                                    <input type="text" required class="form-control" name="recaptcha:website_key" value="{{ old('recaptcha:website_key', config('recaptcha.website_key')) }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings.content.secret_key')</label>
                                <div>
                                    <input type="text" required class="form-control" name="recaptcha:secret_key" value="{{ old('recaptcha:secret_key', config('recaptcha.secret_key')) }}">
                                    <p class="text-muted small">@lang('admin/settings.content.secret_key_hint')</p>
                                </div>
                            </div>
                        </div>
                        @if($showRecaptchaWarning)
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="alert alert-warning no-margin">
                                        @lang('admin/settings.content.recaptcha_hint')
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings.content.http_conn')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings.content.conn_timeout')</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:guzzle:connect_timeout" value="{{ old('pterodactyl:guzzle:connect_timeout', config('pterodactyl.guzzle.connect_timeout')) }}">
                                    <p class="text-muted small">@lang('admin/settings.content.conn_timeout_hint')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings.content.request_timeout')</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:guzzle:timeout" value="{{ old('pterodactyl:guzzle:timeout', config('pterodactyl.guzzle.timeout')) }}">
                                    <p class="text-muted small">@lang('admin/settings.content.request_timeout_hint')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings.content.console')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings.content.message_count')</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:console:count" value="{{ old('pterodactyl:console:count', config('pterodactyl.console.count')) }}">
                                    <p class="text-muted small">@lang('admin/settings.content.message_count_hint')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings.content.freq_tick')</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:console:frequency" value="{{ old('pterodactyl:console:frequency', config('pterodactyl.console.frequency')) }}">
                                    <p class="text-muted small">@lang('admin/settings.content.freq_tick_hint')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-primary">
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">@lang('admin/settings.content.save')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
