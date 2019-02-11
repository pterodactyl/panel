{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    {{ $node->name }}: @lang('admin/nodes_view.configuration.header.title')
@endsection

@section('content-header')
    <h1>{{ $node->name }}@lang('admin/nodes_view.configuration.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/nodes_view.configuration.header.admin')</a></li>
        <li><a href="{{ route('admin.nodes') }}">@lang('admin/nodes_view.header.nodes')</a></li>
        <li><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></li>
        <li class="active">@lang('admin/nodes_view.configuration.header.title')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.nodes.view', $node->id) }}">@lang('admin/nodes_view.content.about')</a></li>
                <li><a href="{{ route('admin.nodes.view.settings', $node->id) }}">@lang('admin/nodes_view.content.settings')</a></li>
                <li class="active"><a href="{{ route('admin.nodes.view.configuration', $node->id) }}">@lang('admin/nodes_view.content.configuration')</a></li>
                <li><a href="{{ route('admin.nodes.view.allocation', $node->id) }}">@lang('admin/nodes_view.content.allocation')</a></li>
                <li><a href="{{ route('admin.nodes.view.servers', $node->id) }}">@lang('admin/nodes_view.content.servers')</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/nodes_view.configuration.content.config')</h3>
            </div>
            <div class="box-body">
                <pre class="no-margin">{{ $node->getConfigurationAsJson(true) }}</pre>
            </div>
            <div class="box-footer">
                <p class="no-margin">@lang('admin/nodes_view.configuration.content.config_hint')</p>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/nodes_view.configuration.content.auto')</h3>
            </div>
            <div class="box-body">
                <p class="text-muted small">@lang('admin/nodes_view.configuration.content.auto_hint')</p>
            </div>
            <div class="box-footer">
                <button type="button" id="configTokenBtn" class="btn btn-sm btn-default" style="width:100%;">@lang('admin/nodes_view.configuration.content.generate')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#configTokenBtn').on('click', function (event) {
        $.getJSON('{{ route('admin.nodes.view.configuration.token', $node->id) }}').done(function (data) {
            swal({
                type: 'success',
                title: '@lang('admin/nodes_view.configuration.content.success_title')',
                text: '@lang('admin/nodes_view.configuration.content.success_textStart')' +
                      '<p>@lang('admin/nodes_view.configuration.content.success_textEnd')<br /><small><pre>npm run configure -- --panel-url {{ config('app.url') }} --token ' + data.token + '</pre></small></p>',
                html: true
            })
        }).fail(function () {
            swal({
                title: '@lang('admin/nodes_view.configuration.content.error_title')',
                text: '@lang('admin/nodes_view.configuration.content.error_text')',
                type: 'error'
            });
        });
    });
    </script>
@endsection
