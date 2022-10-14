@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Delete
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>Delete this server from the panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.servers') }}">Servers</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">Delete</li>
    </ol>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <form id="deleteform" action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Safely Delete Server</h3>
                </div>
                <div class="box-body">
                    <p>This will attempt to remove the server from the associated node and remove data linked to it.</p>
                    <div class="checkbox checkbox-primary no-margin-bottom">
                        <input id="pReturnResourcesSafe" name="return_resources" type="checkbox" value="1" />
                        <label for="pReturnResourcesSafe">Return resources to user on server deletion?</label>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button id="deletebtn" class="btn btn-warning">Safely Delete This Server</button>
                </div>
            </div>
        </div>
    </form>
    <form id="forcedeleteform" action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Forcibly Delete Server</h3>
                </div>
                <div class="box-body">
                    <p>This will remove all server data from the Panel regardless of whether Wings is able to delete the server from the system.</p>
                    <div class="checkbox checkbox-primary no-margin-bottom">
                        <input id="pReturnResources" name="return_resources" type="checkbox" value="1" />
                        <label for="pReturnResources">Return resources to user on server deletion?</label>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="hidden" name="force_delete" value="1" />
                    <button id="forcedeletebtn"" class="btn btn-danger">Forcibly Delete This Server</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#deletebtn').click(function (event) {
        event.preventDefault();
        swal({
            title: 'Delete Server',
            text: 'All data will be removed from the Panel and your node.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: 'orange',
            closeOnConfirm: false
        }, function () {
            $('#deleteform').submit()
        });
    });

    $('#forcedeletebtn').click(function (event) {
        event.preventDefault();
        swal({
            title: 'Delete Server',
            text: 'All data will be removed from the Panel and your node.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: 'red',
            closeOnConfirm: false
        }, function () {
            $('#forcedeleteform').submit()
        });
    });
    </script>
@endsection
