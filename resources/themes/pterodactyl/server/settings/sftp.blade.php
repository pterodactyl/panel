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
@extends('layouts.master')

@section('title')
    @lang('server.config.sftp.header')
@endsection

@section('content-header')
    <h1>@lang('server.config.sftp.header')<small>@lang('server.config.sftp.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">@lang('navigation.server.sftp_settings')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.config.sftp.change_pass')</h3>
            </div>
            @can('reset-sftp', $server)
                <form action="{{ route('server.settings.sftp', $server->uuidShort) }}" method="post">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="sftp_pass" class="control-label">@lang('base.account.new_password')</label>
                            <div>
                                <input type="password" class="form-control" name="sftp_pass" />
                                <p class="text-muted"><small>@lang('auth.password_requirements')</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" class="btn btn-primary btn-sm" value="@lang('base.account.update_pass')" />
                    </div>
                </form>
            @else
                <div class="box-body">
                    <div class="callout callout-warning callout-nomargin">
                        <p>@lang('auth.not_authorized')</p>
                    </div>
                </div>
            @endcan
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.config.sftp.details')</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label class="control-label">@lang('server.config.sftp.conn_addr')</label>
                    <div>
                        <input type="text" class="form-control" readonly value="sftp://{{ $node->fqdn }}:{{ $node->daemonSFTP }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="password" class="control-label">@lang('strings.username')</label>
                    <div>
                        <input type="text" class="form-control" readonly value="{{ $server->username }}" />
                    </div>
                </div>
                @can('view-sftp-password', $server)
                    <div class="form-group">
                        <label for="password" class="control-label">@lang('base.account.current_password')</label>
                        <div>
                            <input type="text" class="form-control" readonly @if(! is_null($server->sftp_password))value="{{ Crypt::decrypt($server->sftp_password) }}"@endif />
                        </div>
                    </div>
                @endcan
            </div>
            <div class="box-footer">
                <p class="small text-muted">@lang('server.config.sftp.warning')</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
