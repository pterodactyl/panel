{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/packs.view.header.title') {{ $pack->name }}
@endsection

@section('content-header')
    <h1>{{ $pack->name }}<small>{{ str_limit($pack->description, 60) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/packs.header.admin')</a></li>
        <li><a href="{{ route('admin.packs') }}">@lang('admin/packs.header.packs')</a></li>
        <li class="active">{{ $pack->name }}</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.packs.view', $pack->id) }}" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/packs.new.content.pack_details')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">@lang('admin/packs.new.content.name')</label>
                        <input name="name" type="text" id="pName" class="form-control" value="{{ $pack->name }}" />
                        <p class="text-muted small">@lang('admin/packs.new.content.name_hint')</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">@lang('admin/packs.content.description')</label>
                        <textarea name="description" id="pDescription" class="form-control" rows="8">{{ $pack->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pVersion" class="form-label">@lang('admin/packs.content.version')</label>
                        <input type="text" name="version" id="pVersion" class="form-control" value="{{ $pack->version }}" />
                        <p class="text-muted small">@lang('admin/packs.new.content.version')</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">@lang('admin/packs.view.header.storage_location')</label>
                        <input type="text" class="form-control" readonly value="{{ storage_path('app/packs/' . $pack->uuid) }}">
                        <p class="text-muted small">@lang('admin/packs.view.header.storage_location_hint')</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/packs.new.content.pack_config')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pEggId" class="form-label">@lang('admin/packs.view.header.association_option')</label>
                        <select id="pEggId" name="egg_id" class="form-control">
                            @foreach($nests as $nest)
                                <optgroup label="{{ $nest->name }}">
                                    @foreach($nest->eggs as $egg)
                                        <option value="{{ $egg->id }}" {{ $pack->egg_id !== $egg->id ?: 'selected' }}>{{ $egg->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">@lang('admin/packs.view.header.association_option_hint')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSelectable" name="selectable" type="checkbox" value="1" {{ ! $pack->selectable ?: 'checked' }}/>
                            <label for="pSelectable">
                                @lang('admin/packs.new.content.selectable')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('admin/packs.new.content.selectable_hint')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pVisible" name="visible" type="checkbox" value="1" {{ ! $pack->visible ?: 'checked' }}/>
                            <label for="pVisible">
                                @lang('admin/packs.new.content.visible')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('admin/packs.new.content.visible_hint')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-warning no-margin-bottom">
                            <input id="pLocked" name="locked" type="checkbox" value="1" {{ ! $pack->locked ?: 'checked' }}/>
                            <label for="pLocked">
                                @lang('admin/packs.new.content.locked')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('admin/packs.new.content.locked_hint')</p>
                    </div>
                </div>
                <div class="box-footer with-border">
                    {!! csrf_field() !!}
                    <button name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right" type="submit">@lang('admin/packs.view.content.save')</button>
                    <button name="_method" value="DELETE" class="btn btn-sm btn-danger pull-left muted muted-hover" type="submit"><i class="fa fa-trash-o"></i></button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/packs.view.content.servers')</h3>
            </div>
            <div class="box-body no-padding table-responsive">
                <table class="table table-hover">
                    <tr>
                        <th>@lang('admin/packs.content.id')</th>
                        <th>@lang('admin/packs.view.content.server_name')</th>
                        <th>@lang('admin/packs.view.content.node')</th>
                        <th>@lang('admin/packs.view.content.owner')</th>
                    </tr>
                    @foreach($pack->servers as $server)
                        <tr>
                            <td><code>{{ $server->uuidShort }}</code></td>
                            <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                            <td><a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a></td>
                            <td><a href="{{ route('admin.users.view', $server->user->id) }}">{{ $server->user->email }}</a></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-6 col-md-5 col-md-offset-7 col-xs-offset-6">
        <form action="{{ route('admin.packs.view.export', $pack->id) }}" method="POST">
            {!! csrf_field() !!}
            <button type="submit" class="btn btn-sm btn-success pull-right">@lang('admin/packs.view.content.export')</button>
        </form>
        <form action="{{ route('admin.packs.view.export', ['id' => $pack->id, 'files' => 'with-files']) }}" method="POST">
            {!! csrf_field() !!}
            <button type="submit" class="btn btn-sm pull-right muted muted-hover" style="margin-right:10px;">@lang('admin/packs.view.content.export_with_archive')</button>
        </form>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pEggId').select2();
    </script>
@endsection
