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
                        <label for="pOptionId" class="form-label">Associated Option</label>
                        <select id="pOptionId" name="option_id" class="form-control">
                            @foreach($services as $service)
                                <optgroup label="{{ $service->name }}">
                                    @foreach($service->options as $option)
                                        <option value="{{ $option->id }}" {{ $pack->option_id !== $option->id ?: 'selected' }}>{{ $option->name }}</option>
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
                    <button name="action" value="delete" class="btn btn-sm btn-danger pull-left muted muted-hover" type="submit"><i class="fa fa-trash-o"></i></button>
                    <button name="action" value="edit" class="btn btn-sm btn-primary pull-right" type="submit">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Stored Files</h3>
            </div>
            <div class="box-body no-padding table-responsive">
                <table class="table table-hover">
                    <tr>
                        <th>Name</th>
                        <th>SHA1 Hash</th>
                        <th>File Size</th>
                    </tr>
                    @foreach($pack->files() as $file)
                        <tr>
                            <td>{{ $file->name }}</td>
                            <td><code>{{ $file->hash }}</code></td>
                            <td>{{ $file->size }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="box-footer">
                <p class="text-muted small">If you would like to modified the stored pack you will need to upload a new <code>archive.tar.gz</code> to the location defined below.</p>
                <p class="text-muted small"><strong>Storage Location:</strong> <code>{{ storage_path('app/packs/' . $pack->uuid) }}</code></p>
            </div>
        </div>
    </div>
</div>
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
        $('#pOptionId').select2();
    </script>
@endsection
