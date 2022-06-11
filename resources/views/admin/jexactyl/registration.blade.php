@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'registration'])

@section('title')
    Jexactyl Settings
@endsection

@section('content-header')
    <h1>User Registration<small>Configure settings for user registration on Jexactyl.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.jexactyl.registration') }}" method="POST">
                <div class="box
                @if($enabled == 'true')
                    box-success
                @else
                    box-danger
                @endif
                ">
                    <div class="box-header with-border">
                        <h3 class="box-title">User Registration</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <div>
                                    <select name="registration:enabled" class="form-control">
                                        <option @if ($enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether people can register an account.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Default Resources</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">CPU Amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:cpu" value="{{ $cpu }}" />
                                    <p class="text-muted"><small>The amount of CPU that should be given to a user on signup in %.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">RAM amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:memory" value="{{ $memory }}" />
                                    <p class="text-muted"><small>The amount of RAM that should be given to a user on signup in MB.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Storage Amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:disk" value="{{ $disk }}" />
                                    <p class="text-muted"><small>The amount of storage that should be given to a user on signup in MB.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Slots Amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:slot" value="{{ $slot }}" />
                                    <p class="text-muted"><small>The amount of server slots that should be given to a user on signup.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Allocation Amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:port" value="{{ $port }}" />
                                    <p class="text-muted"><small>The amount of server ports that should be given to a user on signup.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Backup Amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:backup" value="{{ $backup }}" />
                                    <p class="text-muted"><small>The amount of server backups that should be given to a user on signup.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Database Amount</label>
                                <div>
                                    <input type="text" class="form-control" name="registration:database" value="{{ $database }}" />
                                    <p class="text-muted"><small>The amount of server databases that should be given to a user on signup.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
