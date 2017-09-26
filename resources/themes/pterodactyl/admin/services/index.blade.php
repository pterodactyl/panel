{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Service
@endsection

@section('content-header')
    <h1>Service<small>All services currently available on this system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Service</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Configured Service</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.services.new') }}"><button class="btn btn-primary btn-sm">Create New</button></a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-center">Options</th>
                        <th class="text-center">Packs</th>
                        <th class="text-center">Servers</th>
                    </tr>
                    @foreach($services as $service)
                        <tr>
                            <td class="middle"><a href="{{ route('admin.services.view', $service->id) }}">{{ $service->name }}</a></td>
                            <td class="col-xs-6 middle">{{ $service->description }}</td>
                            <td class="text-center middle"><code>{{ $service->options_count }}</code></td>
                            <td class="text-center middle"><code>{{ $service->packs_count }}</code></td>
                            <td class="text-center middle"><code>{{ $service->servers_count }}</code></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
