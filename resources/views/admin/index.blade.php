@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content-header')
    <h1>Administrative Overview<small>A quick glance at your system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Index</li>
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
                <h3 class="box-title">System Information</h3>
            </div>
            <div class="box-body">
                @if ($version->isLatestPanel())
                    You are running Pterodactyl Panel version <code>{{ config('app.version') }}</code>. Your panel is up-to-date!
                @else
                    Your panel is <strong>not up-to-date!</strong> The latest version is
                    <a href="https://github.com/Pterodactyl/Panel/releases/v{{ $version->getPanel() }}" target="_blank">
                        <code>{{ $version->getPanel() }}</code>
                    </a>
                    and you are currently running version <code>{{ config('app.version') }}</code>.
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-top:15px;">
    <div class="col-xs-6 col-sm-3">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $stats['nodes'] ?? 0 }}</h3>
                <p>Nodes</p>
            </div>
            <div class="icon"><i class="fa fa-server"></i></div>
            <a href="{{ route('admin.nodes') }}" class="small-box-footer">Manage Nodes <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3">
        <div class="small-box bg-green">
            <div class="inner">
                <h3>{{ $stats['users'] ?? 0 }}</h3>
                <p>Users</p>
            </div>
            <div class="icon"><i class="fa fa-users"></i></div>
            <a href="{{ route('admin.users') }}" class="small-box-footer">Manage Users <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $stats['servers'] ?? 0 }}</h3>
                <p>Servers</p>
            </div>
            <div class="icon"><i class="fa fa-gamepad"></i></div>
            <a href="{{ route('admin.servers') }}" class="small-box-footer">Manage Servers <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3">
        <div class="small-box bg-red">
            <div class="inner">
                <h3>{{ $stats['allocations'] ?? 0 }}</h3>
                <p>Allocations</p>
            </div>
            <div class="icon"><i class="fa fa-plug"></i></div>
            <a href="{{ route('admin.nodes') }}" class="small-box-footer">View Allocations <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row" style="margin-top:20px;">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Nodes Overview</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Node</th>
                            <th>Memory (allocated / limit)</th>
                            <th>Disk (allocated / limit)</th>
                            <th>Ports (allocated / total)</th>
                            <th>Maintenance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nodesData as $n)
                            <tr>
                                <td style="vertical-align: middle; text-align:center;">
                                    @php
                                        $heartColor = $n['online'] ? '#29a744' : '#6c757d';
                                        $heartTitle = $n['online']
                                            ? ('Online - checked: ' . ($n['last_checked'] ?? 'now'))
                                            : ('Offline - last checked: ' . ($n['last_checked'] ?? 'unknown'));
                                    @endphp
                                    <i class="fa fa-heart" style="color: {{ $heartColor }};" title="{{ $heartTitle }}"></i>
                                </td>
                                <td>
                                    <a href="{{ route('admin.nodes.view', $n['id']) }}">{{ $n['name'] }}</a>
                                    <div><small>({{ $n['fqdn'] }})</small></div>
                                    @if(!empty($n['last_checked']))
                                        <div><small>Checked: {{ $n['last_checked'] }}</small></div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $n['memory']['used'] }} MB</strong> / {{ $n['memory']['limit'] }} MB
                                    <div class="progress" style="margin-top:6px;height:8px;">
                                        @php
                                            $mPerc = $n['memory']['limit']
                                                ? round(($n['memory']['used'] / $n['memory']['limit']) * 100)
                                                : 0;
                                        @endphp
                                        <div class="progress-bar progress-bar-aqua" style="width: {{ $mPerc }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $n['disk']['used'] }} MB</strong> / {{ $n['disk']['limit'] }} MB
                                    <div class="progress" style="margin-top:6px;height:8px;">
                                        @php
                                            $dPerc = $n['disk']['limit']
                                                ? round(($n['disk']['used'] / $n['disk']['limit']) * 100)
                                                : 0;
                                        @endphp
                                        <div class="progress-bar progress-bar-yellow" style="width: {{ $dPerc }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $n['allocations']['used'] }}</strong> / {{ $n['allocations']['total'] }}
                                </td>
                                <td>
                                    @if($n['maintenance'])
                                        <span class="label label-danger">in maintenance</span>
                                    @else
                                        <span class="label label-success">Not in maintenance</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Added button row --}}
<div class="row" style="margin-top:20px;">
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="{{ $version->getDiscord() }}">
            <button class="btn btn-warning" style="width:100%;">
                <i class="fa fa-fw fa-support"></i> Get Help <small>(via Discord)</small>
            </button>
        </a>
    </div>

    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://pterodactyl.io">
            <button class="btn btn-primary" style="width:100%;">
                <i class="fa fa-fw fa-link"></i> Documentation
            </button>
        </a>
    </div>

    <div class="clearfix visible-xs-block">&nbsp;</div>

    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://github.com/pterodactyl/panel">
            <button class="btn btn-primary" style="width:100%;">
                <i class="fa fa-fw fa-support"></i> GitHub
            </button>
        </a>
    </div>

    <div class="col-xs-6 col-sm-3 text-center">
        <a href="{{ $version->getDonations() }}">
            <button class="btn btn-success" style="width:100%;">
                <i class="fa fa-fw fa-money"></i> Support the Project
            </button>
        </a>
    </div>
</div>
@endsection
