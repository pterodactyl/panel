{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/databases.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/databases.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/databases.header.admin')</a></li>
        <li class="active">@lang('admin/databases.header.dbhost')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/databases.content.list')</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newHostModal">@lang('admin/databases.content.new')</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>@lang('admin/databases.content.id')</th>
                            <th>@lang('admin/databases.content.name')</th>
                            <th>@lang('admin/databases.content.host')</th>
                            <th>@lang('admin/databases.content.port')</th>
                            <th>@lang('admin/databases.content.username')</th>
                            <th class="text-center">@lang('admin/databases.content.database')</th>
                            <th class="text-center">@lang('admin/databases.content.node')</th>
                        </tr>
                        @foreach ($hosts as $host)
                            <tr>
                                <td><code>{{ $host->id }}</code></td>
                                <td><a href="{{ route('admin.databases.view', $host->id) }}">{{ $host->name }}</a></td>
                                <td><code>{{ $host->host }}</code></td>
                                <td><code>{{ $host->port }}</code></td>
                                <td>{{ $host->username }}</td>
                                <td class="text-center">{{ $host->databases_count }}</td>
                                <td class="text-center">
                                    @if(! is_null($host->node))
                                        <a href="{{ route('admin.nodes.view', $host->node->id) }}">{{ $host->node->name }}</a>
                                    @else
                                        <span class="label label-default">@lang('admin/databases.content.none')</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="newHostModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.databases') }}" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">@lang('admin/databases.content.create_new')</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">@lang('admin/databases.content.create_new_name')</label>
                        <input type="text" name="name" id="pName" class="form-control" />
                        <p class="text-muted small">@lang('admin/databases.content.create_new_description')</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="pHost" class="form-label">@lang('admin/databases.content.create_new_host')</label>
                            <input type="text" name="host" id="pHost" class="form-control" />
                            <p class="text-muted small">@lang('admin/databases.content.create_new_host_description')</p>
                        </div>
                        <div class="col-md-6">
                            <label for="pPort" class="form-label">@lang('admin/databases.content.create_new_port')</label>
                            <input type="text" name="port" id="pPort" class="form-control" value="3306"/>
                            <p class="text-muted small">@lang('admin/databases.content.create_new_port_description')</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="pUsername" class="form-label">@lang('admin/databases.content.create_new_username')</label>
                            <input type="text" name="username" id="pUsername" class="form-control" />
                            <p class="text-muted small">@lang('admin/databases.content.create_new_username_description')</p>
                        </div>
                        <div class="col-md-6">
                            <label for="pPassword" class="form-label">@lang('admin/databases.content.create_new_password')</label>
                            <input type="password" name="password" id="pPassword" class="form-control" />
                            <p class="text-muted small">@lang('admin/databases.content.create_new_password_description')</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pNodeId" class="form-label">@lang('admin/databases.content.linked_node')</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            <option value="">@lang('admin/databases.content.none')</option>
                            @foreach($locations as $location)
                                <optgroup label="{{ $location->short }}">
                                    @foreach($location->nodes as $node)
                                        <option value="{{ $node->id }}">{{ $node->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">@lang('admin/databases.content.linked_node_description')</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="text-danger small text-left">@lang('admin/databases.content.footer')</p>
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">@lang('admin/databases.content.cancel')</button>
                    <button type="submit" class="btn btn-success btn-sm">@lang('admin/databases.content.create')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pNodeId').select2();
    </script>
@endsection
