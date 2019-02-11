{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/nodes.new.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/nodes.new.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/nodes.index.header.admin')</a></li>
        <li><a href="{{ route('admin.nodes') }}">@lang('admin/nodes.index.header.nodes')</a></li>
        <li class="active">@lang('admin/nodes.new.header.new')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.nodes.new') }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nodes.new.content.basic')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">@lang('admin/nodes.index.content.name')</label>
                        <input type="text" name="name" id="pName" class="form-control" value="{{ old('name') }}"/>
                        <p class="text-muted small">@lang('admin/nodes.new.content.limit')</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">@lang('admin/nodes.new.content.description')</label>
                        <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pLocationId" class="form-label">@lang('admin/nodes.index.content.location')</label>
                        <select name="location_id" id="pLocationId">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->id != old('location_id') ?: 'selected' }}>{{ $location->short }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">@lang('admin/nodes.new.content.visibility')</label>
                        <div>
                            <div class="radio radio-success radio-inline">

                                <input type="radio" id="pPublicTrue" value="1" name="public" checked>
                                <label for="pPublicTrue"> @lang('admin/nodes.new.content.public')  </label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pPublicFalse" value="0" name="public">
                                <label for="pPublicFalse"> @lang('admin/nodes.new.content.private') </label>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes.new.content.private_hint')
                    </div>
                    <div class="form-group">
                        <label for="pFQDN" class="form-label">@lang('admin/nodes.new.content.fqdn')</label>
                        <input type="text" name="fqdn" id="pFQDN" class="form-control" value="{{ old('fqdn') }}"/>
                        <p class="text-muted small">@lang('admin/nodes.new.content.fqdn_hint')</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">@lang('admin/nodes.new.content.ssl')</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pSSLTrue" value="https" name="scheme" checked>
                                <label for="pSSLTrue"> @lang('admin/nodes.new.content.use_ssl')</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pSSLFalse" value="http" name="scheme" @if(request()->isSecure()) disabled @endif>
                                <label for="pSSLFalse"> @lang('admin/nodes.new.content.use_http')</label>
                            </div>
                        </div>
                        @if(request()->isSecure())
                            <p class="text-danger small">@lang('admin/nodes.new.content.ssl_hint')</p>
                        @else
                            <p class="text-muted small">@lang('admin/nodes.new.content.http_hint')</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label">@lang('admin/nodes.new.content.proxy_on')</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pProxyFalse" value="0" name="behind_proxy" checked>
                                <label for="pProxyFalse"> @lang('admin/nodes.new.content.proxy_off') </label>
                            </div>
                            <div class="radio radio-info radio-inline">
                                <input type="radio" id="pProxyTrue" value="1" name="behind_proxy">
                                <label for="pProxyTrue"> @lang('admin/nodes.new.content.proxy_on') </label>
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/nodes.new.content.proxy_hint')</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/nodes.new.content.configuration')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-xs-12">
                            <label for="pDaemonBase" class="form-label">@lang('admin/nodes.new.content.file_dir')</label>
                            <input type="text" name="daemonBase" id="pDaemonBase" class="form-control" value="/srv/daemon-data" />
                            <p class="text-muted small">@lang('admin/nodes.new.content.file_dir_hint')</p>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pMemory" class="form-label">@lang('admin/nodes.new.content.total_memory')</label>
                            <div class="input-group">
                                <input type="text" name="memory" data-multiplicator="true" class="form-control" id="pMemory" value="{{ old('memory') }}"/>
                                <span class="input-group-addon">MB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pMemoryOverallocate" class="form-label">@lang('admin/nodes.new.content.memory_overallocation')</label>
                            <div class="input-group">
                                <input type="text" name="memory_overallocate" class="form-control" id="pMemoryOverallocate" value="{{ old('memory_overallocate') }}"/>
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">@lang('admin/nodes.new.content.memory_overallocation_hint')</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pDisk" class="form-label">@lang('admin/nodes.new.content.total_disk')</label>
                            <div class="input-group">
                                <input type="text" name="disk" data-multiplicator="true" class="form-control" id="pDisk" value="{{ old('disk') }}"/>
                                <span class="input-group-addon">MB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pDiskOverallocate" class="form-label">@lang('admin/nodes.new.content.disk_overallocation')</label>
                            <div class="input-group">
                                <input type="text" name="disk_overallocate" class="form-control" id="pDiskOverallocate" value="{{ old('disk_overallocate') }}"/>
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">@lang('admin/nodes.new.content.disk_overallocation_hint')</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pDaemonListen" class="form-label">Daemon Port</label>
                            <input type="text" name="daemonListen" class="form-control" id="pDaemonListen" value="8080" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pDaemonSFTP" class="form-label">@lang('admin/nodes.new.content.sftp_port')</label>
                            <input type="text" name="daemonSFTP" class="form-control" id="pDaemonSFTP" value="2022" />
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">@lang('admin/nodes.new.content.sftp_port_hint')</p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success pull-right">@lang('admin/nodes.new.content.create_node')</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pLocationId').select2();
    </script>
@endsection