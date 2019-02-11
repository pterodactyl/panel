{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/servers_view.content.server') — {{ $server->name }}: @lang('admin/servers_view.database.header.title')
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>@lang('admin/servers_view.database.header.overview')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li><a href="{{ route('admin.servers') }}">@lang('admin/servers_view.header.servers')</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">@lang('admin/servers_view.database.header.title')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.servers.view', $server->id) }}">@lang('admin/servers_view.content.about')</a></li>
                @if($server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">@lang('admin/servers_view.content.details')</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">@lang('admin/servers_view.header.build_config')</a></li>
                    <li><a href="{{ route('admin.servers.view.startup', $server->id) }}">@lang('admin/servers_view.content.startup')</a></li>
                    <li class="active"><a href="{{ route('admin.servers.view.database', $server->id) }}">@lang('admin/servers_view.content.database')</a></li>
                    <li><a href="{{ route('admin.servers.view.manage', $server->id) }}">@lang('admin/servers_view.content.manage')</a></li>
                @endif
                <li class="tab-danger"><a href="{{ route('admin.servers.view.delete', $server->id) }}">@lang('admin/servers_view.content.delete')</a></li>
                <li class="tab-success"><a href="{{ route('server.index', $server->uuidShort) }}"><i class="fa fa-external-link"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-7">
        <div class="alert alert-info">
            @lang('admin/servers_view.database.content.pw_infoStart')<a href="{{ route('server.databases.index', ['server' => $server->uuidShort]) }}">@lang('admin/servers_view.database.content.pw_infoEnd')
        </div>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.database.content.active_db')</h3>
            </div>
            <div class="box-body table-responsible no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>@lang('admin/servers_view.content.database')</th>
                        <th>@lang('admin/servers_view.database.content.username')</th>
                        <th>@lang('admin/servers_view.database.content.conn_from')</th>
                        <th>@lang('admin/servers_view.database.content.host')</th>
                        <th></th>
                    </tr>
                    @foreach($server->databases as $database)
                        <tr>
                            <td>{{ $database->database }}</td>
                            <td>{{ $database->username }}</td>
                            <td>{{ $database->remote }}</td>
                            <td><code>{{ $database->host->host }}:{{ $database->host->port }}</code></td>
                            <td class="text-center">
                                <button data-action="reset-password" data-id="{{ $database->id }}" class="btn btn-xs btn-primary"><i class="fa fa-refresh"></i></button>
                                <button data-action="remove" data-id="{{ $database->id }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/servers_view.database.content.create_new_db')</h3>
            </div>
            <form action="{{ route('admin.servers.view.database', $server->id) }}" method="POST">
                <div class="box-body">
                    <div class="form-group">
                        <label for="pDatabaseHostId" class="control-label">@lang('admin/servers_view.database.content.db_host')</label>
                        <select id="pDatabaseHostId" name="database_host_id" class="form-control">
                            @foreach($hosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted small">@lang('admin/servers_view.database.content.db_host_hint')</p>
                    </div>
                    <div class="form-group">
                        <label for="pDatabaseName" class="control-label">@lang('admin/servers_view.content.database')</label>
                        <div class="input-group">
                            <span class="input-group-addon">s{{ $server->id }}_</span>
                            <input id="pDatabaseName" type="text" name="database" class="form-control" placeholder="database" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pRemote" class="control-label">@lang('admin/servers_view.database.content.conn')</label>
                        <input id="pRemote" type="text" name="remote" class="form-control" value="%" />
                        <p class="text-muted small">@lang('admin/servers_view.database.content.conn_hint')</p>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <p class="text-muted small no-margin">@lang('admin/servers_view.database.content.auth_hint')</p>
                    <input type="submit" class="btn btn-sm btn-success pull-right" value="Create Database" />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#pDatabaseHost').select2();
    $('[data-action="remove"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            title: '',
            type: 'warning',
            text: '@lang('admin/servers_view.database.content.auth_hint')',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: Router.route('admin.servers.view.database.delete', { server: '{{ $server->id }}', database: self.data('id') }),
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function () {
                self.parent().parent().slideUp();
                swal.close();
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: '@lang('admin/servers_view.database.content.ooopsi')',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : '@lang('admin/servers_view.database.content.error')'
                });
            });
        });
    });
    $('[data-action="reset-password"]').click(function (e) {
        e.preventDefault();
        var block = $(this);
        $(this).addClass('disabled').find('i').addClass('fa-spin');
        $.ajax({
            type: 'PATCH',
            url: Router.route('admin.servers.view.database', { server: '{{ $server->id }}' }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            data: { database: $(this).data('id') },
        }).done(function (data) {
            swal({
                type: 'success',
                title: '',
                text: '@lang('admin/servers_view.database.content.success')',
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error(jqXHR);
            var error = '@lang('admin/servers_view.database.content.error')';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: '@lang('admin/servers_view.database.content.ooopsi')',
                text: error
            });
        }).always(function () {
            block.removeClass('disabled').find('i').removeClass('fa-spin');
        });
    });
    </script>
@endsection
