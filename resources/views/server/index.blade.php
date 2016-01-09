@extends('layouts.master')

@section('title')
    Viewing Server: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    <script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.1/highcharts.js"></script>
@endsection

@section('content')
<div class="col-md-12">
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li id="triggerConsoleView" class="active"><a href="#console" data-toggle="tab">{{ trans('server.index.control') }}</a></li>
        <li><a href="#stats" data-toggle="tab">{{ trans('server.index.usage') }}</a></li>
        @can('allocation', $server)<li><a href="#allocation" data-toggle="tab">{{ trans('server.index.allocation') }}</a></li>@endcan
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="console">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <textarea id="live_console" class="form-control console" readonly="readonly">Loading Previous Content...</textarea>
                        </div>
                        <div class="col-md-6">
                            <hr />
                            @can('command', $server)
                                <form action="#" method="post" id="console_command" style="display:none;">
                                    <fieldset>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="command" id="ccmd" placeholder="{{ trans('server.index.command') }}" />
                                            <span class="input-group-btn">
                                                <button id="sending_command" class="btn btn-primary btn-sm">&rarr;</button>
                                            </span>
                                        </div>
                                    </fieldset>
                                </form>
                                <div class="alert alert-danger" id="sc_resp" style="display:none;margin-top: 15px;"></div>
                            @endcan
                        </div>
                        <div class="col-md-6" style="text-align:center;">
                            <hr />
                            @can('power', $server)
                                <button class="btn btn-success btn-sm disabled" data-attr="power" data-action="start">Start</button>
                                <button class="btn btn-primary btn-sm disabled" data-attr="power" data-action="restart">Restart</button>
                                <button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="stop">Stop</button>
                                <button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="kill"><i class="fa fa-ban" data-toggle="tooltip" data-placement="top" title="Kill Running Process"></i></button>
                                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pauseConsole" id="pause_console"><small><i class="fa fa-pause fa-fw"></i></small></button>
                                <div id="pw_resp" style="display:none;margin-top: 15px;"></div>
                            @endcan
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12" id="stats_players">
                            <h3>Active Players</h3><hr />
                            <div id="players_notice" class="alert alert-info">
                                <i class="fa fa-spinner fa-spin"></i> Waiting for response from server...
                            </div>
                            <span id="toggle_players" style="display:none;">
                                <p class="text-muted">No players are online.</p>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="stats">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-11 text-center" id="chart_memory" style="height:250px;"></div>
                        <div class="col-xs-11 text-center" id="chart_cpu" style="height:250px;"></div>
                    </div>
                </div>
            </div>
        </div>
        @can('allocation', $server)
            <div class="tab-pane" id="allocation">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="alert alert-info">Below is a listing of all avaliable IPs and Ports for your service. To change the default connection address for your server, simply click on the one you would like to make default below.</div>
                        <ul class="nav nav-pills nav-stacked" id="conn_options">
                            @foreach ($allocations as $allocation)
                                <li role="presentation" @if($allocation->ip === $server->ip && $allocation->port === $server->port) class="active" @endif><a href="#/set-connnection/{{ $allocation->ip }}:{{ $allocation->port }}" data-action="set-connection" data-connection="{{ $allocation->ip }}:{{ $allocation->port }}">{{ $allocation->ip }} <span class="badge">{{ $allocation->port }}</span></a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endcan
    </div>
    <div class="row">
        <div class="col-xs-11" id="col11_setter"></div>
    </div>
</div>
<div class="modal fade" id="pauseConsole" tabindex="-1" role="dialog" aria-labelledby="PauseConsole" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="PauseConsole">{{ trans('server.index.scrollstop') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id="paused_console" class="form-control console" readonly="readonly"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('strings.close') }}</button>
            </div>
        </div>
    </div>
</div>
@if($server->a_serviceFile === 'minecraft')
    <script src="{{ route('server.js', [$server->uuidShort, 'minecraft/eula.js']) }}"></script>
@endif
<script>
$(window).load(function () {
    $('[data-toggle="tooltip"]').tooltip();

    // -----------------+
    // Charting Methods |
    // -----------------+
    $(window).resize(function() {
        $('#chart_memory').highcharts().setSize($('#col11_setter').width(), 250);
        $('#chart_cpu').highcharts().setSize($('#col11_setter').width(), 250);
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
            '#113F8C',
            '#00A1CB',
            '#01A4A4',
            '#61AE24',
            '#D0D102',
            '#D70060',
            '#E54028',
            '#F18D05',
            '#616161',
            '#32742C',
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'Live Memory Usage',
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
            '#01A4A4',
            '#61AE24',
            '#D0D102',
            '#D70060',
            '#E54028',
            '#F18D05',
            '#616161',
            '#32742C',
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'Live CPU Usage',
        },
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                var s = '<b>CPU Usage</b>';
                var i = 0;
                var t = 0;
                $.each(this.points, function () {
                    t = t + this.y;
                    i++;
                    s += '<br/>' + this.series.name + ': ' +
                        this.y + '%';
                });

                t = parseFloat(t).toFixed(3).toString();

                if (i > 1) {
                    return s + '<br />Combined: ' + t;
                } else {
                    return s;
                }
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
                stacking: 'normal',
                lineWidth: 1,
                marker: {
                    enabled: false,
                },
            },
        },
        legend: {
            enabled: true
        },
        series: [{
            name: 'Core 0',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    });

    // Socket Recieves New Server Stats
    socket.on('proc', function (proc) {
        var MemoryChart = $('#chart_memory').highcharts();
        MemoryChart.series[0].addPoint(parseInt(proc.data.memory.total / (1024 * 1024)), true, true);

        var CPUChart = $('#chart_cpu').highcharts();

        // if({{ $server->cpu }} > 0) {
        //     CPUChart.series[0].addPoint(parseFloat(((proc.data.cpu.total / {{ $server->cpu }}) * 100).toFixed(3).toString()), true, true);
        // } else {
        //     CPUChart.series[0].addPoint(proc.data.cpu.total, true, true);
        // }
        for (i = 0, length = proc.data.cpu.cores.length; i < length; i++) {
            if (typeof CPUChart.series[i] === 'undefined') {
                CPUChart.addSeries({
                    name: 'Core ' + i,
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                });
            }
            if({{ $server->cpu }} > 0) {
                CPUChart.series[i].addPoint(parseFloat(((proc.data.cpu.cores[i] / {{ $server->cpu }}) * 100).toFixed(3).toString()), true, true);
            } else {
                CPUChart.series[i].addPoint(proc.data.cpu.cores[i], true, true);
            }
        }
    });

    // Socket Recieves New Query
    socket.on('query', function (data){
        if($('#players_notice').is(':visible')){
            $('#players_notice').hide();
            $('#toggle_players').show();
        }
        if(data['data'].players != undefined && data['data'].players.length !== 0){
            $('#toggle_players').html('');
            $.each(data['data'].players, function(id, d) {
                $('#toggle_players').append('<code>' + d.name + '</code>,');
            });
        }else{
            $('#toggle_players').html('<p class=\'text-muted\'>No players are currently online.</p>');
        }
    });

    // New Console Data Recieved
    socket.on('console', function (data) {
        $('#live_console').val($('#live_console').val() + data.line);
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });

    // Update Listings on Initial Status
    socket.on('initial_status', function (data) {
        if (data.status !== 0) {
            $.ajax({
                type: 'GET',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/log',
                timeout: 10000
            }).done(function(data) {
                $('#live_console').val(data);
                $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                alert('Unable to load initial server log, try reloading the page.');
            });
        } else {
            $('#live_console').val('Server is currently off.');
        }
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Update Listings on Status
    socket.on('status', function (data) {
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Scroll to the top of the Console when switching to that tab.
    $('#triggerConsoleView').click(function () {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });
    if($('triggerConsoleView').is(':visible')) {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    }
    $('a[data-toggle=\'tab\']').on('shown.bs.tab', function (e) {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });

    // Load Paused Console with Live Console Data
    $('#pause_console').click(function(){
        $('#paused_console').val($('#live_console').val());
    });

    function updatePlayerListVisibility(data) {
        // Server is On or Starting
        if(data !== 0) {
            $('#stats_players').show();
        } else {
            $('#stats_players').hide();
        }
    }

    @can('set-connection', $server)
        // Send Request
        $('[data-action="set-connection"]').click(function (event) {
            event.preventDefault();
            var element = $(this);
            if (element.hasClass('active')) {
                return;
            }

            $.ajax({
                method: 'POST',
                url: '/server/{{ $server->uuidShort }}/ajax/set-connection',
                data: {
                    connection: element.data('connection')
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                $('#conn_options').find('li.active').removeClass('active');
                element.parent().addClass('active');
                alert(data);
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                if (typeof jqXHR.responseJSON.error === 'undefined' || jqXHR.responseJSON.error === '') {
                    return alert('An error occured while attempting to perform this action.');
                } else {
                    return alert(jqXHR.responseJSON.error);
                }
            });
        });
    @endcan

    @can('command', $server)
        // Send Command to Server
        $('#console_command').submit(function (event) {

            event.preventDefault();
            var ccmd = $('#ccmd').val();
            if (ccmd == '') {
                return;
            }

            $('#sending_command').html('<i class=\'fa fa-refresh fa-spin\'></i>').addClass('disabled');
            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                contentType: 'application/json; charset=utf-8',
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/command',
                timeout: 10000,
                data: JSON.stringify({ command: ccmd })
            }).fail(function (jqXHR) {
                $('#sc_resp').html('Unable to process your request. Please try again.').fadeIn().delay(5000).fadeOut();
            }).always(function () {
                $('#sending_command').html('&rarr;').removeClass('disabled');
                $('#ccmd').val('');
            });
        });
    @endcan
    @can('power', $server)
        var can_run = true;
        function updateServerPowerControls (data) {

            // Reset Console Data
            if (data === 2) {
                $('#live_console').val($('#live_console').val() + '\n --+ Server Detected as Booting + --\n');
                $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
            }

            // Server is On or Starting
            if(data == 1 || data == 2) {
                $("#console_command").slideDown();
                $('[data-attr="power"][data-action="start"]').addClass('disabled');
                $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').removeClass('disabled');
            } else {
                $("#console_command").slideUp();
                $('[data-attr="power"][data-action="start"]').removeClass('disabled');
                $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').addClass('disabled');
            }

            if(data !== 0) {
                $('[data-attr="power"][data-action="kill"]').removeClass('disabled');
            } else {
                $('[data-attr="power"][data-action="kill"]').addClass('disabled');
            }

        }

        $('[data-attr="power"]').click(function (event) {
            event.preventDefault();
            var action = $(this).data('action');
            if (action === 'kill') {
                var killConfirm = confirm('WARNING: This operation will not save your server data gracefully. You should only use this if your server is failing to respond to normal stop commands.');
            } else { var killConfirm = true; }

            if(killConfirm) {
                $.ajax({
                    type: 'PUT',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({
                        action: action
                    }),
                    url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power',
                    timeout: 10000
                }).fail(function(jqXHR) {
                    var error = 'An unknown error occured processing this request.';
                    if (typeof jqXHR.responseJSON.error !== 'undefined') {
                        error = jqXHR.responseJSON.error;
                    }
                    $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to process your request. Please try again. (' + error + ')').fadeIn().delay(5000).fadeOut();
                });
            }

        });

    @endcan
});

$(document).ready(function () {
    $('.server-index').addClass('active');
});
</script>
@endsection
