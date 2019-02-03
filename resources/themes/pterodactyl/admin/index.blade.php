{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    @lang('admin/admin.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/admin.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/admin.header.admin')</a></li>
        <li class="active">@lang('admin/admin.header.index')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box
            @if($version->isLatestPanel())
                box-success
            @else
                box-danger
            @endif
        ">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/admin.content.title')</h3>
            </div>
            <div class="box-body">
                @if ($version->isLatestPanel())
                    @lang('admin/admin.content.isLatest')
                @else
                    @lang('admin/admin.content.notLatest')
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="{{ $version->getDiscord() }}"><button class="btn btn-warning" style="width:100%;"><i class="fa fa-fw fa-support"></i> @lang('admin/admin.button.discord')</button></a>
    </div>
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://docs.pterodactyl.io"><button class="btn btn-primary" style="width:100%;"><i class="fa fa-fw fa-link"></i> @lang('admin/admin.button.doc')</button></a>
    </div>
    <div class="clearfix visible-xs-block">&nbsp;</div>
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://github.com/Pterodactyl/Panel"><button class="btn btn-primary" style="width:100%;"><i class="fa fa-fw fa-support"></i> @lang('admin/admin.button.github')</button></a>
    </div>
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://donorbox.org/pterodactyl"><button class="btn btn-success" style="width:100%;"><i class="fa fa-fw fa-money"></i> @lang('admin/admin.button.support')</button></a>
    </div>
</div>
@endsection
