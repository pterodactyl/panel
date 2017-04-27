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
@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>{{ $server->uuid }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.servers') }}">Servers</a></li>
        <li class="active">{{ $server->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.servers.view', $server->id) }}">About</a></li>
                @if($server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">Details</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">Build Configuration</a></li>
                    <li><a href="{{ route('admin.servers.view.startup', $server->id) }}">Startup</a></li>
                    <li><a href="{{ route('admin.servers.view.database', $server->id) }}">Database</a></li>
                @endif
                <li><a href="{{ route('admin.servers.view.manage', $server->id) }}">Manage</a></li>
                <li class="tab-danger"><a href="{{ route('admin.servers.view.delete', $server->id) }}">Delete</a></li>
                <li class="tab-success"><a href="{{ route('server.index', $server->uuidShort) }}"><i class="fa fa-external-link"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Information</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <tr>
                                <td>UUID</td>
                                <td>{{ $server->uuid }}</td>
                            </tr>
                            <tr>
                                <td>Docker Container ID</td>
                                <td data-attr="container-id"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                            </tr>
                            <tr>
                                <td>Docker User ID</td>
                                <td data-attr="container-user"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                            </tr>
                            <tr>
                                <td>Docker Container Name</td>
                                <td>{{ $server->username }}</td>
                            </tr>
                            <tr>
                                <td>Service</td>
                                <td>
                                    <a href="{{ route('admin.services.view', $server->option->service->id) }}">{{ $server->option->service->name }}</a> ::
                                    <a href="{{ route('admin.services.option.view', $server->option->id) }}">{{ $server->option->name }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td>{{ $server->name }}</td>
                            </tr>
                            <tr>
                                <td>Memory</td>
                                <td><code>{{ $server->memory }}MB</code> / <code data-toggle="tooltip" data-placement="top" title="Swap Space">{{ $server->swap }}MB</code></td>
                            </tr>
                            <tr>
                                <td><abbr title="Out of Memory">OOM</abbr> Killer</td>
                                <td>{!! ($server->oom_disabled === 0) ? '<span class="label label-success">Enabled</span>' : '<span class="label label-default">Disabled</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Disk Space</td>
                                <td><code>{{ $server->disk }}MB</code></td>
                            </tr>
                            <tr>
                                <td>Block IO Weight</td>
                                <td><code>{{ $server->io }}</code></td>
                            </tr>
                            <tr>
                                <td>CPU Limit</td>
                                <td><code>{{ $server->cpu }}%</code></td>
                            </tr>
                            <tr>
                                <td>Default Connection</td>
                                <td><code>{{ $server->allocation->ip }}:{{ $server->allocation->port }}</code></td>
                            </tr>
                            <tr>
                                <td>Connection Alias</td>
                                <td>
                                    @if($server->allocation->alias !== $server->allocation->ip)
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                    @else
                                        <span class="label label-default">No Alias Assigned</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="box box-primary">
            <div class="box-body" style="padding-bottom: 0px;">
                <div class="row">
                    @if($server->suspended)
                        <div class="col-sm-12">
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                    <h3 class="no-margin">Suspended</h3>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($server->installed !== 1)
                        <div class="col-sm-12">
                            <div class="small-box {{ (! $server->installed) ? 'bg-blue' : 'bg-maroon' }}">
                                <div class="inner">
                                    <h3 class="no-margin">{{ (! $server->installed) ? 'Installing' : 'Install Failed' }}</h3>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="col-sm-12">
                        <div class="small-box bg-gray">
                            <div class="inner">
                                <h3>{{ str_limit($server->user->username, 8) }}</h3>
                                <p>Server Owner</p>
                            </div>
                            <div class="icon"><i class="fa fa-user"></i></div>
                            <a href="{{ route('admin.users.view', $server->user->id) }}" class="small-box-footer">
                                More info <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="small-box bg-gray">
                            <div class="inner">
                                <h3>{{ str_limit($server->node->name, 8) }}</h3>
                                <p>Server Node</p>
                            </div>
                            <div class="icon"><i class="fa fa-codepen"></i></div>
                            <a href="{{ route('admin.nodes.view', $server->node->id) }}" class="small-box-footer">
                                More info <i class="fa fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    (function checkServerInfo() {
        $.ajax({
            type: 'GET',
            headers: {
                'X-Access-Token': '{{ $server->daemonSecret }}',
                'X-Access-Server': '{{ $server->uuid }}'
            },
            url: '{{ $server->node->scheme }}://{{ $server->node->fqdn }}:{{ $server->node->daemonListen }}/server',
            dataType: 'json',
            timeout: 5000,
        }).done(function (data) {
            $('td[data-attr="container-id"]').html('<code>' + data.container.id + '</code>');
            $('td[data-attr="container-user"]').html('<code>' + data.user + '</code>');
        }).fail(function (jqXHR) {
            $('td[data-attr="container-id"]').html('<code>error</code>');
            $('td[data-attr="container-user"]').html('<code>error</code>');
            console.error(jqXHR);
        }).always(function () {
            setTimeout(checkServerInfo, 60000);
        })
    })();
    </script>
@endsection
