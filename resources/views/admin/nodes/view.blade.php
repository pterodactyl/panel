@extends('layouts.admin')

@section('title')
    Managing Node: {{ $node->name }}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/nodes">Nodes</a></li>
        <li class="active">{{ $node->name }}</li>
    </ul>
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#tab_about" data-toggle="tab">About</a></li>
        <li><a href="#tab_settings" data-toggle="tab">Settings</a></li>
        <li><a href="#tab_allocation" data-toggle="tab">Allocation</a></li>
        <li><a href="#tab_servers" data-toggle="tab">Servers</a></li>
        <li><a href="#tab_delete" data-toggle="tab">Delete</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="tab_about">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    About Node
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_settings">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    Settings
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_allocation">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    Allocations
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_servers">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        The data below is updated every 30 seconds, or on page load. CPU usage is displayed relative to the assigned CPU allocation. For example, if a server is assigned <code>10%</code> and the CPU usage below displays <code>90%</code> that means the server is using <code>9%</code> of the total system CPU.
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Owner</th>
                                <th>Service</th>
                                <th class="text-center">Memory</th>
                                <th class="text-center">Disk</th>
                                <th class="text-center">CPU</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servers as $server)
                                <tr data-server="{{ $server->uuid }}">
                                    <td><a href="/admin/servers/view/{{ $server->id }}">{{ $server->name }}</a></td>
                                    <td><a href="/admin/users/view/{{ $server->owner }}"><code>{{ $server->a_ownerEmail }}</a></a></td>
                                    <td>{{ $server->a_serviceName }}</td>
                                    <td class="text-center"><span data-action="memory">--</span> / {{ $server->memory }} MB</td>
                                    <td class="text-center">{{ $server->disk }} MB</td>
                                    <td class="text-center"><span data-action="cpu" data-cpumax="{{ $server->cpu }}">--</span> %</td>
                                    <td class="text-center" data-action="status">--</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-md-12 text-center">{!! $servers->appends(['tab' => 'tab_servers'])->render() !!}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_delete">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    Delete
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/nodes']").addClass('active');

    // Gets all of the server data in one go.
    function getServerData() {
        var Status = {
            0: 'Off',
            1: 'On',
            2: 'Starting',
            3: 'Stopping'
        };
        $.ajax({
            method: 'GET',
            url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/servers',
            headers: {
                'X-Access-Token': '{{ $node->daemonSecret }}'
            }
        }).done(function (data) {
            $.each(data, function (uuid, info) {
                var element = $('tr[data-server="' + uuid + '"]');
                element.find('[data-action="status"]').html(Status[info.status]);
                if (info.status !== 0) {
                    var cpuMax = element.find('[data-action="cpu"]').data('cpumax');
                    var currentCpu = info.proc.cpu.total;
                    if (cpuMax !== 0) {
                        currentCpu = parseFloat(((info.proc.cpu.total / cpuMax) * 100).toFixed(2).toString());
                    }
                    element.find('[data-action="memory"]').html(parseInt(info.proc.memory.total / (1024 * 1024)));
                    element.find('[data-action="cpu"]').html(currentCpu);
                } else {
                    element.find('[data-action="memory"]').html('--');
                    element.find('[data-action="cpu"]').html('--');
                }
            });
        }).fail(function (jqXHR) {
            console.error(jqXHR);
        });
    }
    getServerData();
    window.setInterval(function() {
        getServerData();
    }, 30000);
});
</script>
@endsection
