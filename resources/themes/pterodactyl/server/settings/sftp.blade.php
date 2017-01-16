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
    SFTP Settings
@endsection

@section('content-header')
    <h1>SFTP Configuration<small>Account details for SFTP connections.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">{{ trans('strings.home') }}</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>{{ trans('strings.configuration') }}</li>
        <li class="active">{{ trans('strings.sftp') }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Change SFTP Password</h3>
            </div>
            @can('reset-sftp', $server)
                <form action="{{ route('server.settings.sftp', $server->uuidShort) }}" method="post">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="sftp_pass" class="control-label">{{ trans('base.account.new_password') }}</label>
                            <div>
                                <input type="password" class="form-control" name="sftp_pass" />
                                <p class="text-muted"><small>{{ trans('auth.password_requirements') }}</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" class="btn btn-primary btn-sm" value="{{ trans('base.account.update_pass') }}" />
                    </div>
                </form>
            @else
                <div class="box-body">
                    <div class="callout callout-warning callout-nomargin">
                        <p>You are not authorized to perform this action.</p>
                    </div>
                </div>
            @endcan
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">SFTP Details</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-8">
                        <label for="new_email" class="control-label">Connection Address</label>
                        <div>
                            <input type="text" class="form-control" readonly value="{{ $node->fqdn }}" />
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="new_email" class="control-label">Port</label>
                        <div>
                            <input type="text" class="form-control" readonly value="{{ $node->daemonSFTP }}" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password" class="control-label">Username</label>
                    <div>
                        <input type="text" class="form-control" readonly value="{{ $server->username }}" />
                    </div>
                </div>
                @can('view-sftp-password', $server)
                    <div class="form-group">
                        <label for="password" class="control-label">{{ trans('base.account.current_password') }}</label>
                        <div>
                            <input type="text" class="form-control" readonly @if(! is_null($server->sftp_password))value="{{ Crypt::decrypt($server->sftp_password) }}"@endif />
                        </div>
                    </div>
                @endcan
            </div>
            <div class="box-footer">
                <p class="small text-muted">Ensure that your client is set to use <strong>SFTP</strong> and not FTP or FTPS for connections, there is a difference between the protocols.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
