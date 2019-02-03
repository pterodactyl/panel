{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/locations.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/locations.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/locations.header.admin')</a></li>
        <li class="active">@lang('admin/locations.header.locations')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/locations.content.location_list')</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newLocationModal">@lang('admin/locations.content.create_new')</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>@lang('admin/locations.content.id')</th>
                            <th>@lang('admin/locations.content.short_code')</th>
                            <th>@lang('admin/locations.content.description')</th>
                            <th class="text-center">@lang('admin/locations.content.nodes')</th>
                            <th class="text-center">@lang('admin/locations.content.servers')</th>
                        </tr>
                        @foreach ($locations as $location)
                            <tr>
                                <td><code>{{ $location->id }}</code></td>
                                <td><a href="{{ route('admin.locations.view', $location->id) }}">{{ $location->short }}</a></td>
                                <td>{{ $location->long }}</td>
                                <td class="text-center">{{ $location->nodes_count }}</td>
                                <td class="text-center">{{ $location->servers_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="newLocationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.locations') }}" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">@lang('admin/locations.content.create_location')</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="pShortModal" class="form-label">@lang('admin/locations.content.short_code')</label>
                            <input type="text" name="short" id="pShortModal" class="form-control" />
                            <p class="text-muted small">@lang('admin/locations.content.short_code_description')</p>
                        </div>
                        <div class="col-md-12">
                            <label for="pLongModal" class="form-label">@lang('admin/locations.content.description')</label>
                            <textarea name="long" id="pLongModal" class="form-control" rows="4"></textarea>
                            <p class="text-muted small">@lang('admin/locations.content.description_description')</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">@lang('admin/locations.content.cancel')</button>
                    <button type="submit" class="btn btn-success btn-sm">@lang('admin/locations.content.create')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
