@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'renewal'])

@section('title')
    Jexactyl Renewals
@endsection

@section('content-header')
    <h1>Jexactyl Renewals<small>Configure Jexactyl's server renewal system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.jexactyl.renewal') }}" method="POST">
                <div class="box
                    @if($enabled == 'true')
                        box-success
                    @else
                        box-danger
                    @endif
                ">
                    <div class="box-header with-border">
                        <i class="fa fa-clock-o"></i> <h3 class="box-title">Server Renewals <small>Configure settings for server renewals.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Renewal System</label>
                                <div>
                                    <select name="enabled" class="form-control">
                                        <option @if ($enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users must renew their servers.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Default Renewal Timer</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" id="default" name="default" class="form-control" value="{{ $default }}" />
                                        <span class="input-group-addon">days</span>
                                    </div>
                                    <p class="text-muted"><small>Determines the amount of days that servers have before their first renewal is due.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Renewal cost</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" id="cost" name="cost" class="form-control" value="{{ $cost }}" />
                                        <span class="input-group-addon">credits</span>
                                    </div>
                                    <p class="text-muted"><small>Determines the amount of credits that a renewal costs.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {!! csrf_field() !!}
                <button type="submit" name="_method" value="PATCH" class="btn btn-default pull-right">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
