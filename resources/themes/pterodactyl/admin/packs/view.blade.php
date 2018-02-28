{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Packs &rarr; View &rarr; {{ $pack->name }}
@endsection

@section('content-header')
    <h1>{{ $pack->name }}<small>{{ str_limit($pack->description, 60) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.packs') }}">Packs</a></li>
        <li class="active">{{ $pack->name }}</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.packs.view', $pack->id) }}" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Pack Details</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">Name</label>
                        <input name="name" type="text" id="pName" class="form-control" value="{{ $pack->name }}" />
                        <p class="text-muted small">A short but descriptive name of what this pack is. For example, <code>Counter Strike: Source</code> if it is a Counter Strike package.</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">Description</label>
                        <textarea name="description" id="pDescription" class="form-control" rows="8">{{ $pack->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pVersion" class="form-label">Version</label>
                        <input type="text" name="version" id="pVersion" class="form-control" value="{{ $pack->version }}" />
                        <p class="text-muted small">The version of this package, or the version of the files contained within the package.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Storage Location</label>
                        <input type="text" class="form-control" readonly value="{{ storage_path('app/packs/' . $pack->uuid) }}">
                        <p class="text-muted small">If you would like to modify the stored pack you will need to upload a new <code>archive.tar.gz</code> to the location defined above.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Pack Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pEggId" class="form-label">Associated Option</label>
                        <select id="pEggId" name="egg_id" class="form-control">
                            @foreach($nests as $nest)
                                <optgroup label="{{ $nest->name }}">
                                    @foreach($nest->eggs as $egg)
                                        <option value="{{ $egg->id }}" {{ $pack->egg_id !== $egg->id ?: 'selected' }}>{{ $egg->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">The option that this pack is assocaited with. Only servers that are assigned this option will be able to access this pack. This assigned option <em>cannot</em> be changed if servers are attached to this pack.</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSelectable" name="selectable" type="checkbox" value="1" {{ ! $pack->selectable ?: 'checked' }}/>
                            <label for="pSelectable">
                                Selectable
                            </label>
                        </div>
                        <p class="text-muted small">Check this box if user should be able to select this pack to install on their servers.</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pVisible" name="visible" type="checkbox" value="1" {{ ! $pack->visible ?: 'checked' }}/>
                            <label for="pVisible">
                                Visible
                            </label>
                        </div>
                        <p class="text-muted small">Check this box if this pack is visible in the dropdown menu. If this pack is assigned to a server it will be visible regardless of this setting.</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-warning no-margin-bottom">
                            <input id="pLocked" name="locked" type="checkbox" value="1" {{ ! $pack->locked ?: 'checked' }}/>
                            <label for="pLocked">
                                Locked
                            </label>
                        </div>
                        <p class="text-muted small">Check this box if servers assigned this pack should not be able to switch to a different pack.</p>
                    </div>
                </div>
                <div class="box-footer with-border">
                    {!! csrf_field() !!}
                    <button name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right" type="submit">Save</button>
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
                <h3 class="box-title">Servers Using This Pack</h3>
            </div>
            <div class="box-body no-padding table-responsive">
                <table class="table table-hover">
                    <tr>
                        <th>ID</th>
                        <th>Server Name</th>
                        <th>Node</th>
                        <th>Owner</th>
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
            <button type="submit" class="btn btn-sm btn-success pull-right">Export</button>
        </form>
        <form action="{{ route('admin.packs.view.export', ['id' => $pack->id, 'files' => 'with-files']) }}" method="POST">
            {!! csrf_field() !!}
            <button type="submit" class="btn btn-sm pull-right muted muted-hover" style="margin-right:10px;">Export with Archive</button>
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
