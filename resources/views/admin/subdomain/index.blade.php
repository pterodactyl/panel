@extends('layouts.admin')

@section('title')
    Audit Logs
@endsection

@section('content-header')
    <h1>Audit Logs <small>You can view changes on all servers.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Audit Logs</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Logs</h3>
                    <div class="box-tools">

                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            
                            <th>Id</th>
                            <th>User</th>
                            <th>Server</th>
                            <th>Actions</th>
                            <th>File</th>
                        </tr>
                        @foreach ($domains as $domain)
                            <tr>
                                <td>{{$domain['id']}}</td>
                                <td>{{$domain['user_id']}}</td>
                                <td>{{$domain['server_id']}}</td>
                                <td>{{$domain['action']}}</td>
                                <td>{{$domain['metadata']}}</td>
                                
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

@endsection

