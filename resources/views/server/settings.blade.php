{{--
    Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
--}}
@extends('layouts.master')

@section('title')
    Server Settings
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Server Settings</h3><hr />
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        @can('view-sftp', $server)<li class="active"><a href="#tab_sftp" data-toggle="tab">SFTP Settings</a></li>@endcan
        @can('view-startup', $server)<li><a href="#tab_startup" data-toggle="tab">Startup Configuration</a></li>@endcan
    </ul>
    <div class="tab-content">
        @can('view-sftp', $server)
            <div class="tab-pane active" id="tab_sftp">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">SFTP Connection Address:</label>
                                <div>
                                    <input type="text" readonly="readonly" class="form-control" value="{{ $node->fqdn }}:{{ $node->daemonSFTP }}" />
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">SFTP Username:</label>
                                <div>
                                    <input type="text" readonly="readonly" class="form-control" value="{{ $server->username }}" />
                                </div>
                            </div>
                        </div>
                        @can('reset-sftp', $server)
                            <form action="{{ route('server.settings.sftp', $server->uuidShort) }}" method="POST">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label class="control-label">New SFTP Password:</label>
                                        <div>
                                            <input type="password" name="sftp_pass" class="form-control" />
                                            <p class="text-muted"><small>Passwords must meet the following requirements: at least one uppercase character, one lowercase character, one digit, and be at least 8 characters in length. <a href="#" data-action="generate-password">Click here</a> to generate one to use.</small></p>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="control-label">&nbsp;</label>
                                        <div>
                                            {!! csrf_field() !!}
                                            <input type="submit" class="btn btn-sm btn-primary" value="Update Password" />
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @endcan
        @can('view-startup', $server)
            <div class="tab-pane" id="tab_startup">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        Startup
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
<script>
$(document).ready(function () {
    $('.server-settings').addClass('active');
});
</script>
@endsection
