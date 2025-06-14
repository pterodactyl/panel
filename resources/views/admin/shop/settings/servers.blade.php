@extends('layouts.admin')

@section('title')
    Settings | Server Settings
@endsection

@section('content-header')
    <h1>Server Settings <small>Set server settings.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Server Settings</li>
    </ol>
@endsection

@section('content')
    @include('admin.shop.settings.partials.navigation')

    <div class="row">
        <div class="col-xs-12 col-lg-4 col-lg-offset-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Server Settings</h3>
                </div>
                <form method="post" action="{{ route('admin.shop.settings.servers') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="deleteDays">Delete Days</label>
                            <input type="number" class="form-control" name="deleteDays" id="deleteDays" value="{{ old('deleteDays', $deleteDays) }}" placeholder="0">
                            <p class="text-muted small">Server will be deleted when it's expired after the selected days. If you set more than 0, server will be suspended firstly. <code>0</code> is delete immediately.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
