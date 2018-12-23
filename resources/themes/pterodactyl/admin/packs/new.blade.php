{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Packs &rarr; New
@endsection

@section('content-header')
    <h1>New Pack<small>Create a new pack on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.packs') }}">Packs</a></li>
        <li class="active">New</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.packs.new') }}">Configure Manually</a></li>
                <li><a href="#modal" id="toggleModal">Install From Template</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.packs.new') }}" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Pack Details</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">Name</label>
                        <input name="name" type="text" id="pName" class="form-control" value="{{ old('name') }}" />
                        <p class="text-muted small">A short but descriptive name of what this pack is. For example, <code>Counter Strike: Source</code> if it is a Counter Strike package.</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">Description</label>
                        <textarea name="description" id="pDescription" class="form-control" rows="8">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pVersion" class="form-label">Version</label>
                        <input type="text" name="version" id="pVersion" class="form-control" value="{{ old('version') }}" />
                        <p class="text-muted small">The version of this package, or the version of the files contained within the package.</p>
                    </div>
                    <div class="form-group">
                        <label for="pEggId" class="form-label">Associated Egg</label>
                        <select id="pEggId" name="egg_id" class="form-control">
                            @foreach($nests as $nest)
                                <optgroup label="{{ $nest->name }}">
                                    @foreach($nest->eggs as $egg)
                                        <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">The option that this pack is associated with. Only servers that are assigned this option will be able to access this pack.</p>
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
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSelectable" name="selectable" type="checkbox" value="1" checked/>
                            <label for="pSelectable">
                                Selectable
                            </label>
                        </div>
                        <p class="text-muted small">Check this box if user should be able to select this pack to install on their servers.</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pVisible" name="visible" type="checkbox" value="1" checked/>
                            <label for="pVisible">
                                Visible
                            </label>
                        </div>
                        <p class="text-muted small">Check this box if this pack is visible in the dropdown menu. If this pack is assigned to a server it will be visible regardless of this setting.</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-warning no-margin-bottom">
                            <input id="pLocked" name="locked" type="checkbox" value="1"/>
                            <label for="pLocked">
                                Locked
                            </label>
                        </div>
                        <p class="text-muted small">Check this box if servers assigned this pack should not be able to switch to a different pack.</p>
                    </div>
                    <hr />
                    <div class="form-group no-margin-bottom">
                        <label for="pFileUpload" class="form-label">Pack Archive</label>
                        <input type="file" accept=".tar.gz, application/gzip" name="file_upload" class="well well-sm" style="width:100%"/>
                        <p class="text-muted small">This package file must be a <code>.tar.gz</code> archive of pack files to be decompressed into the server folder.</p>
                        <p class="text-muted small">If your file is larger than <code>50MB</code> it is recommended to upload it using SFTP. Once you have added this pack to the system, a path will be provided where you should upload the file.</p>
                        <div class="callout callout-info callout-slim no-margin-bottom">
                            <p class="text-muted small"><strong>This server is currently configured with the following limits:</strong><br /><code>upload_max_filesize={{ ini_get('upload_max_filesize') }}</code><br /><code>post_max_size={{ ini_get('post_max_size') }}</code><br /><br />If your file is larger than either of those values this request will fail.</p>
                        </div>
                    </div>
                </div>
                <div class="box-footer with-border">
                    {!! csrf_field() !!}
                    <button class="btn btn-sm btn-success pull-right" type="submit">Create Pack</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pEggId').select2();
        $('#toggleModal').on('click', function (event) {
            event.preventDefault();

            $.ajax({
                method: 'GET',
                url: Router.route('admin.packs.new.template'),
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).fail(function (jqXhr) {
                console.error(jqXhr);
                alert('There was an error trying to create the upload modal.');
            }).done(function (data) {
                $(data).modal();
                $('#pEggIdModal').select2();
            });
        });
    </script>
@endsection
