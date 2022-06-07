@extends('layouts.admin')

@section('title')
    Jexactyl Settings
@endsection

@section('content-header')
    <h1>Jexactyl<small>Configure Jexactyl-specific settings for the Panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
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
                <h3 class="box-title">Software Release</h3>
            </div>
            <div class="box-body">
                @if ($version->isLatestPanel())
                    You are running Jexactyl <code>{{ config('app.version') }}</code>. 
                @else
                    Jexactyl is not up-to-date. Latest release is <a href="https://github.com/jexactyl/jexactyl/releases/v{{ $version->getPanel() }}" target="_blank"><code>{{ $version->getPanel() }}</code></a>.
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <form action="{{ route('admin.jexactyl') }}" method="POST">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Jexactyl Storefront</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="control-label">Enabled</label>
                            <div>
                                <select name="store:enabled" class="form-control">
                                    <option value="false">Disabled</option>
                                    <option value="true">Enabled</option>
                                </select>
                                <p class="text-muted"><small>Determines whether users can access the store UI.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Resource Pricing</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 50% CPU</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:cpu" value="{{ $cpu }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 50% CPU.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 1GB RAM</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:memory" value="{{ $memory }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 1GB of RAM.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 1GB Disk</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:disk" value="{{ $disk }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 1GB of disk.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 1 Server Slot</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:slot" value="{{ $slot }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 1 server slot.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 1 Network Allocation</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:port" value="{{ $port }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 1 port.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 1 Server Backup</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:backup" value="{{ $backup }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 1 backup.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Cost per 1 Server Database</label>
                            <div>
                                <input type="text" class="form-control" name="store:cost:database" value="{{ $database }}" />
                                <p class="text-muted"><small>Used to calculate the total cost for 1 database.</small></p>
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
