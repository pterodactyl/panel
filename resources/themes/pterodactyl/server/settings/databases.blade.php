{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.config.database.header')
@endsection

@section('content-header')
    <h1>@lang('server.config.database.header')<small>@lang('server.config.database.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">@lang('navigation.server.databases')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.config.database.your_dbs')</h3>
            </div>
            @if(count($databases) > 0)
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>@lang('strings.database')</th>
                                <th>@lang('strings.username')</th>
                                <th>@lang('strings.password')</th>
                                <th>@lang('server.config.database.host')</th>
                                @can('reset-db-password', $server)<td></td>@endcan
                            </tr>
                            @foreach($databases as $database)
                                <tr>
                                    <td class="middle">{{ $database->database }}</td>
                                    <td class="middle">{{ $database->username }}</td>
                                    <td class="middle"><code data-attr="set-password">{{ Crypt::decrypt($database->password) }}</code></td>
                                    <td class="middle"><code>{{ $database->host->host }}:{{ $database->host->port }}</code></td>
                                    @can('reset-db-password', $server)
                                        <td>
                                            <button class="btn btn-xs btn-primary pull-right" data-action="reset-password" data-id="{{ $database->id }}"><i class="fa fa-fw fa-refresh"></i> @lang('server.config.database.reset_password')</button>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="box-body">
                    <div class="callout callout-info callout-nomargin">
                        @lang('server.config.database.no_dbs')
                        @if(Auth::user()->root_admin === 1)
                            <a href="{{ route('admin.servers.view', [
                                'id' => $server->id,
                                'tab' => 'tab_database'
                            ]) }}" target="_blank">@lang('server.config.database.add_db')</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script>
    @can('reset-db-password', $server)
        $('[data-action="reset-password"]').click(function (e) {
            e.preventDefault();
            var block = $(this);
            $(this).addClass('disabled').find('i').addClass('fa-spin');
            $.ajax({
                type: 'POST',
                url: Router.route('server.ajax.reset-database-password', { server: Pterodactyl.server.uuidShort }),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                },
                data: {
                    database: $(this).data('id')
                }
            }).done(function (data) {
                block.parent().parent().find('[data-attr="set-password"]').html(data);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error(jqXHR);
                var error = 'An error occured while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: error
                });
            }).always(function () {
                block.removeClass('disabled').find('i').removeClass('fa-spin');
            });
        });
    @endcan
    </script>
@endsection
