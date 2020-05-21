{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}

@extends('layouts.admin')

@section('title')
    Mounts
@endsection

@section('content-header')
    <h1>Mounts<small>SoonTM</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Mounts</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Mount List</h3>

                    <div class="box-tools">
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newMountModal">Create New</button>
                    </div>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Source</th>
                                <th>Target</th>
                                <th class="text-center">Eggs</th>
                                <th class="text-center">Nodes</th>
                                <th class="text-center">Servers</th>
                            </tr>

                            @foreach ($mounts as $mount)
                                <tr>
                                    <td><code>{{ $mount->id }}</code></td>
                                    <td><a href="{{ route('admin.locations.view', $mount->id) }}">{{ $mount->name }}</a></td>
                                    <td>{{ $mount->source }}</td>
                                    <td>{{ $mount->target }}</td>
                                    <td class="text-center">{{ $mount->eggs_count }}</td>
                                    <td class="text-center">{{ $mount->nodes_count }}</td>
                                    <td class="text-center">{{ $mount->servers_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newMountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.mounts') }}" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="color: #FFFFFF">&times;</span>
                        </button>

                        <h4 class="modal-title">Create Mount</h4>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="pName" class="form-label">Name</label>
                                <input type="text" id="pName" name="name" class="form-control" />
                                <p class="text-muted small">Thiccc boi name used to separate this mount from another!</p>
                            </div>

                            <div class="col-md-12">
                                <label for="pDescription" class="form-label">Description</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="4"></textarea>
                                <p class="text-muted small">A longer description of this mount. Must be less than 255 characters.</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
