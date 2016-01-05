@extends('layouts.admin')

@section('title')
    Managing Node: {{ $node->name }}
@endsection

@section('scripts')
    @parent
    <script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.1/highcharts.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/socket.io/1.3.7/socket.io.min.js"></script>
    <script src="{{ asset('js/bootstrap-notify.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $.notifyDefaults({
                placement: {
                    from: 'bottom',
                    align: 'right'
                },
                newest_on_top: true,
                delay: 2000,
                animate: {
                    enter: 'animated fadeInUp',
                    exit: 'animated fadeOutDown'
                }
            });
        });
    </script>
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
        <li><a href="#tab_configuration" data-toggle="tab">Configuration</a></li>
        <li><a href="#tab_allocation" data-toggle="tab">Allocation</a></li>
        <li><a href="#tab_servers" data-toggle="tab">Servers</a></li>
        <li><a href="#tab_delete" data-toggle="tab">Delete</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="tab_about">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <table class="table table-striped" style="margin-bottom:0;">
                        <tbody>
                            <tr>
                                <td>Total Servers</td>
                                <td>{{ count($servers) }}</td>
                            </tr>
                            <tr>
                                <td>Memory Allocated</td>
                                <td><strong class="{{ ($stats->memory < ($node->memory * .8)) ? 'text-success' : 'text-danger' }}">{{ $stats->memory }} MB</strong> of
                                    @if(!is_null($node->memory_overallocate))
                                        <abbr data-toggle="tooltip" data-placement="top" title="Allows up to {{ ($node->memory * (1 + ($node->memory_overallocate / 100)) - $node->memory) }} MB over">{{ $node->memory }}</abbr>
                                    @else
                                        {{ $node->memory }}
                                    @endif
                                     MB
                                </td>
                            </tr>
                            <tr>
                                <td>Disk Allocated</td>
                                <td><strong class="{{ ($stats->disk < ($node->disk * .8)) ? 'text-success' : 'text-danger' }}">{{ $stats->disk }} MB</strong> of
                                    @if(!is_null($node->disk_overallocate))
                                        <abbr data-toggle="tooltip" data-placement="top" title="Allows up to {{ ($node->disk * (1 + ($node->disk_overallocate / 100)) - $node->disk) }} MB over">{{ $node->disk }}</abbr>
                                    @else
                                        {{ $node->disk }}
                                    @endif
                                     MB
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-11 text-center" id="chart_memory" style="height:250px;"></div>
                        <div class="col-xs-11 text-center" id="chart_cpu" style="height:250px;"></div>
                        <div class="col-xs-11 text-center" id="chart_players" style="height:250px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_settings">
            <form method="POST" action="/admin/nodes/view/{{ $node->id }}">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="alert alert-warning">
                            Changing some details below may require that you change the configuration file on the node as well as restart the daemon. They have been marked with <span class="label label-warning"><i class="fa fa-power-off"></i></span> below.
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="name" class="control-label">Node Name</label>
                                <div>
                                    <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name', $node->name) }}" />
                                    <p class="text-muted"><small>Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="name" class="control-label">Location</label>
                                <div>
                                    <select name="location" class="form-control">
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ (old('location', $node->location) === $location->id) ? 'checked' : '' }}>{{ $location->long }} ({{ $location->short }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="public" class="control-label">Public <sup><a data-toggle="tooltip" data-placement="top" title="Allow automatic allocation to this Node?">?</a></sup></label>
                                <div>
                                    <input type="radio" name="public" value="1" {{ (old('public', $node->public) === '1') ? 'checked' : '' }} id="public_1" checked> <label for="public_1" style="padding-left:5px;">Yes</label><br />
                                    <input type="radio" name="public" value="0" {{ (old('public', $node->public) === '0') ? 'checked' : '' }} id="public_0"> <label for="public_0" style="padding-left:5px;">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="fqdn" class="control-label">Fully Qualified Domain Name</label>
                                <div>
                                    <input type="text" autocomplete="off" name="fqdn" class="form-control" value="{{ old('fqdn', $node->fqdn) }}" />
                                </div>
                                <p class="text-muted"><small>This <strong>must</strong> be a fully qualified domain name, you may not enter an IP address or a domain that does not exist.
                                    <a tabindex="0" data-toggle="popover" data-trigger="focus" title="Why do I need a FQDN?" data-content="In order to secure communications between your server and this node we use SSL. We cannot generate a SSL certificate for IP Addresses, and as such you will need to provide a FQDN.">Why?</a>
                                </small></p>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="scheme" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Secure Socket Layer</label>
                                <div class="row" style="padding: 7px 0;">
                                    <div class="col-xs-6">
                                        <input type="radio" name="scheme" value="https" id="scheme_ssl" {{ (old('scheme', $node->scheme) === 'https') ? 'checked' : '' }}/> <label for="scheme_ssl" style="padding-left: 5px;">Enable HTTPS/SSL</label>
                                    </div>
                                    <div class="col-xs-6">
                                        <input type="radio" name="scheme" value="http" id="scheme_nossl" {{ (old('scheme', $node->scheme) === 'http') ? 'checked' : '' }}/> <label for="scheme_nossl" style="padding-left: 5px;">Disable HTTPS/SSL</label>
                                    </div>
                                </div>
                                <p class="text-muted"><small>You should always leave SSL enabled for nodes. Disabling SSL could allow a malicious user to intercept traffic between the panel and the daemon potentially exposing sensitive information.</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="memory" class="control-label">Total Memory</label>
                                <div class="input-group">
                                    <input type="text" name="memory" class="form-control" value="{{ old('memory', $node->memory) }}"/>
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="memory_overallocate" class="control-label">Overallocate</label>
                                <div class="input-group">
                                    <input type="text" name="memory_overallocate" class="form-control" value="{{ old('memory_overallocate', $node->memory_overallocate) }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="disk" class="control-label">Disk Space</label>
                                <div class="input-group">
                                    <input type="text" name="disk" class="form-control" value="{{ old('disk', $node->disk) }}"/>
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="disk_overallocate" class="control-label">Overallocate</label>
                                <div class="input-group">
                                    <input type="text" name="disk_overallocate" class="form-control" value="{{ old('disk_overallocate', $node->disk_overallocate) }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted"><small>Enter the total amount of disk space and memory avaliable for new servers. If you would like to allow overallocation of disk space or memory enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="daemonListen" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Daemon Port</label>
                                        <div>
                                            <input type="text" name="daemonListen" class="form-control" value="{{ old('daemonListen', $node->daemonListen) }}"/>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="daemonSFTP" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Daemon SFTP Port</label>
                                        <div>
                                            <input type="text" name="daemonSFTP" class="form-control" value="{{ old('daemonSFTP', $node->daemonSFTP) }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="text-muted"><small>The daemon runs its own SFTP management container and does not use the SSHd process on the main physical server. <Strong>Do not use the same port that you have assigned for your physcial server's SSH process.</strong></small></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="reset_secret" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Reset Daemon Key</label>
                                        <div style="padding: 7px 0;">
                                            <input type="checkbox" name="reset_secret" id="reset_secret" /> Reset Daemon Master Key
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <p class="text-muted"><small>Resetting the daemon master key will void any request coming from the old key. This key is used for all sensitive operations on the daemon including server creation and deletion. We suggest changing this key regularly for security.</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                {!! csrf_field() !!}
                                <input type="submit" class="btn btn-sm btn-primary" value="Update Node Information" />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="tab-pane" id="tab_configuration">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        Below is the configuration file for your daemon on this node. We recommend <strong>not</strong> simply copy and pasting the code below unless you know what you are doing. You should run the <code>auto-installer</code> or <code>auto-updater</code> to setup the daemon.
                    </div>
                    <div class="col-md-12">
                        <pre><code>{
    "web": {
        "listen": {{ $node->daemonListen }},
        "ssl": {
            "enabled": {{ $node->sceheme === 'https' ? 'true' : 'false' }},
            "certificate": "~/.ssl/ssl.cert",
            "key": "~/.ssl/ssl.key"
        }
    },
    "docker": {
        "socket": "/var/run/docker.sock"
    },
    "sftp": {
        "path": "{{ $node->daemonBase }}",
        "port": {{ $node->daemonSFTP }},
        "container": "container_id"
    },
    "logger": {
        "path": "logs/",
        "src": false,
        "level": "info",
        "period": "1d",
        "count": 3
    },
    "remote": {
        "download": "{{ url('/remote/download') }}"
    },
    "uploads": {
        "maximumSize": 1000000
    },
    "keys": [
        "{{ $node->daemonSecret }}"
    ]
}</code></pre>
                    </div>
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
                        The data below is live output from the daemon. CPU usage is displayed relative to the assigned CPU allocation. For example, if a server is assigned <code>10%</code> and the CPU usage below displays <code>90%</code> that means the server is using <code>9%</code> of the total system CPU.
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
    <div class="row">
        <div class="col-xs-11" id="col11_setter"></div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/nodes']").addClass('active');
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover({
        placement: 'auto'
    });

    var notifySocketError = false;
    var Status = {
        0: 'Off',
        1: 'On',
        2: 'Starting',
        3: 'Stopping'
    };

    // -----------------+
    // Charting Methods |
    // -----------------+
    $(window).resize(function() {
        $('#chart_memory').highcharts().setSize($('#col11_setter').width(), 250);
        $('#chart_cpu').highcharts().setSize($('#col11_setter').width(), 250);
        $('#chart_players').highcharts().setSize($('#col11_setter').width(), 250);
    });
    $('#chart_memory').highcharts({
        chart: {
            type: 'area',
            animation: Highcharts.svg,
            marginRight: 10,
            renderTo: 'container',
            width: $('#col11_setter').width()
        },
        colors: [
            '#113F8C'
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'Memory Usage of All Servers',
        },
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                var s = '<b>Memory Usage</b>';

                $.each(this.points, function () {
                    s += '<br/>' + this.series.name + ': ' +
                        this.y + 'MB';
                });

                return s;
            },
        },
        xAxis: {
            visible: false,
        },
        yAxis: {
            title: {
                text: 'Memory Usage (MB)',
            },
            plotLines: [{
                value: 0,
                width: 1,
            }],
        },
        plotOptions: {
            area: {
                fillOpacity: 0.10,
                marker: {
                    enabled: false,
                },
            },
        },
        legend: {
            enabled: false
        },
        series: [{
            name: 'Total Memory',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    });

    $('#chart_cpu').highcharts({
        chart: {
            type: 'area',
            animation: Highcharts.svg,
            marginRight: 10,
            renderTo: 'container',
            width: $('#col11_setter').width()
        },
        colors: [
            '#00A1CB',
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'CPU Usage of all Servers',
        },
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                var s = '<b>CPU Usage</b>';

                $.each(this.points, function () {
                    s += '<br/>' + this.series.name + ': ' +
                        this.y + '%';
                });

                return s;
            },
        },
        xAxis: {
            visible: false,
        },
        yAxis: {
            title: {
                text: 'CPU Usage (%)',
            },
            plotLines: [{
                value: 0,
                width: 1,
            }],
        },
        plotOptions: {
            area: {
                fillOpacity: 0.10,
                marker: {
                    enabled: false,
                },
            },
        },
        legend: {
            enabled: true
        },
        series: [{
            name: 'Total CPU',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    });

    $('#chart_players').highcharts({
        chart: {
            type: 'area',
            animation: Highcharts.svg,
            marginRight: 10,
            renderTo: 'container',
            width: $('#col11_setter').width()
        },
        colors: [
            '#01A4A4',
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'Total Players on All Servers',
        },
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                var s = '<b>Total Players</b>';

                $.each(this.points, function () {
                    s += '<br/>' + this.series.name + ': ' + this.y;
                });

                return s;
            },
        },
        xAxis: {
            visible: false,
        },
        yAxis: {
            title: {
                text: 'Total Players',
            },
            plotLines: [{
                value: 0,
                width: 1,
            }],
        },
        plotOptions: {
            area: {
                fillOpacity: 0.10,
                marker: {
                    enabled: false,
                },
            },
        },
        legend: {
            enabled: true
        },
        series: [{
            name: 'Total Players',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    });

    // Main Socket Object
    var socket = io('{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/stats/', {
        'query': 'token={{ $node->daemonSecret }}'
    });

    // Socket Failed to Connect
    socket.io.on('connect_error', function (err) {
        if(typeof notifySocketError !== 'object') {
            notifySocketError = $.notify({
                message: '{!! trans('server.ajax.socket_error') !!}'
            }, {
                type: 'danger',
                delay: 0
            });
        }
    });

    // Connected to Socket Successfully
    socket.on('connect', function () {
        if (notifySocketError !== false) {
            notifySocketError.close();
            notifySocketError = false;
        }
    });

    socket.on('error', function (err) {
        console.error('There was an error while attemping to connect to the websocket: ' + err + '\n\nPlease try loading this page again.');
    });

    socket.on('live-stats', function (data) {
        var CPUChart = $('#chart_cpu').highcharts();
        var MemoryChart = $('#chart_memory').highcharts();
        var PlayerChart = $('#chart_players').highcharts();

        CPUChart.series[0].addPoint(data.stats.cpu, true, true);
        MemoryChart.series[0].addPoint(parseInt(data.stats.memory / (1024 * 1024)), true, true);
        PlayerChart.series[0].addPoint(data.stats.players, true, true);

        $.each(data.servers, function (uuid, info) {
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
    });

});
</script>
@endsection
