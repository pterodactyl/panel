{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/packs.new.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/packs.new.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/packs.header.admin')</a></li>
        <li><a href="{{ route('admin.packs') }}">@lang('admin/packs.header.packs')</a></li>
        <li class="active">@lang('admin/packs.new.header.new')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.packs.new') }}">@lang('admin/packs.new.content.manual')</a></li>
                <li><a href="#modal" id="toggleModal">@lang('admin/packs.new.content.template')</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.packs.new') }}" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/packs.new.content.pack_details')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">@lang('admin/packs.new.content.name')</label>
                        <input name="name" type="text" id="pName" class="form-control" value="{{ old('name') }}" />
                        <p class="text-muted small">@lang('admin/packs.new.content.name_hint')</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">@lang('admin/packs.content.description')</label>
                        <textarea name="description" id="pDescription" class="form-control" rows="8">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pVersion" class="form-label">@lang('admin/packs.content.version')</label>
                        <input type="text" name="version" id="pVersion" class="form-control" value="{{ old('version') }}" />
                        <p class="text-muted small">@lang('admin/packs.new.content.version')</p>
                    </div>
                    <div class="form-group">
                        <label for="pEggId" class="form-label">@lang('admin/packs.modal.associated_egg')</label>
                        <select id="pEggId" name="egg_id" class="form-control">
                            @foreach($nests as $nest)
                                <optgroup label="{{ $nest->name }}">
                                    @foreach($nest->eggs as $egg)
                                        <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">@lang('admin/packs.new.content.associated_egg')</p>
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
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSelectable" name="selectable" type="checkbox" value="1" checked/>
                            <label for="pSelectable">
                                @lang('admin/packs.new.content.pack_config')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('admin/packs.new.content.selectable_hint')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pVisible" name="visible" type="checkbox" value="1" checked/>
                            <label for="pVisible">
                                @lang('admin/packs.new.content.visible')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('admin/packs.new.content.visible_hint')</p>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-warning no-margin-bottom">
                            <input id="pLocked" name="locked" type="checkbox" value="1"/>
                            <label for="pLocked">
                                @lang('admin/packs.new.content.locked')
                            </label>
                        </div>
                        <p class="text-muted small">@lang('admin/packs.new.content.locked')</p>
                    </div>
                    <hr />
                    <div class="form-group no-margin-bottom">
                        <label for="pFileUpload" class="form-label">@lang('admin/packs.new.content.pack_archive')</label>
                        <input type="file" accept=".tar.gz, application/gzip" name="file_upload" class="well well-sm" style="width:100%"/>
                        <p class="text-muted small">@lang('admin/packs.new.content.pack_archive_hint')</p>
                        <div class="callout callout-info callout-slim no-margin-bottom">
                            <p class="text-muted small">@lang('admin/packs.new.content.max_sizeStart')<br /><code>upload_max_filesize={{ ini_get('upload_max_filesize') }}</code><br /><code>post_max_size={{ ini_get('post_max_size') }}</code><br /><br />@lang('admin/packs.new.content.max_sizeEnd')</p>
                        </div>
                    </div>
                </div>
                <div class="box-footer with-border">
                    {!! csrf_field() !!}
                    <button class="btn btn-sm btn-success pull-right" type="submit">@lang('admin/packs.new.content.create_pack')</button>
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
