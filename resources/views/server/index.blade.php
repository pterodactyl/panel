@extends('layouts.master')

@section('title')
    Viewing Server: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    <script src="{{ asset('js/chartjs/chart.core.js') }}"></script>
    <script src="{{ asset('js/chartjs/chart.bar.js') }}"></script>
@endsection

@section('content')
<div class="col-md-9">
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ $message }}
            </div>
        @endforeach
    @endforeach
    <ul class="nav nav-tabs" id="config_tabs">
        <li class="active"><a href="#stats" data-toggle="tab">{{ trans('server.index.info_use') }}</a></li>
        <li id="triggerConsoleView"><a href="#console" data-toggle="tab">{{ trans('server.index.control') }}</a></li>
    </ul><br />
    <div class="tab-content">
        <div class="tab-pane active" id="stats">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="nopad">{{ trans('server.index.memory_use') }}</h3><hr />
                    <div class="row centered">
                        <canvas id="memoryChart" width="280" height="150" style="margin-left:20px;"></canvas>
                        <p style="text-align:center;margin-top:-15px;" class="text-muted"><small>{{ trans('server.index.xaxis') }}</small></p>
                        <p class="graph-yaxis hidden-xs hidden-sm text-muted" style="margin-top:-50px !important;"><small>{{ trans('server.index.memory_use') }} (Mb)</small></p>
                        <p class="graph-yaxis hidden-lg hidden-md text-muted" style="margin-top:-65px !important;margin-left: 100px !important;"><small>{{ trans('server.index.memory_use') }} (Mb)</small></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <h3 class="nopad">{{ trans('server.index.cpu_use') }}</h3><hr />
                    <div class="row centered">
                        <canvas id="cpuChart" width="280" height="150" style="margin-left:20px;"></canvas>
                        <p style="text-align:center;margin-top:-15px;" class="text-muted"><small>{{ trans('server.index.xaxis') }}</small></p>
                        <p class="graph-yaxis hidden-sm hidden-xs text-muted" style="margin-top:-65px !important;"><small>{{ trans('server.index.cpu_use') }} (%)</small></p>
                        <p class="graph-yaxis hidden-lg hidden-md text-muted" style="margin-top:-65px !important;margin-left: 100px !important;"><small>{{ trans('server.index.cpu_use') }} (%)</small></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12" id="stats_players">
                    <h3 class="nopad">Active Players</h3><hr />
                    <div id="players_notice" class="alert alert-info">
                        <i class="fa fa-spinner fa-spin"></i> Waiting for response from server...
                    </div>
                    <span id="toggle_players" style="display:none;">
                        <p class="text-muted">No players are online.</p>
                    </span>
                </div>
                <div class="col-md-12">
                    <h3>{{ trans('server.index.server_info') }}</h3><hr />
                    <table class="table table-striped table-bordered table-hover">
                        <tbody>
                            <tr>
                                <td><strong>{{ trans('server.index.connection') }}</strong></td>
                                <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>{{ trans('strings.node') }}</strong></td>
                                <td>{{ $node->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ trans('server.index.mem_limit') }}</strong></td>
                                <td>{{ $server->memory }} MB</td>
                            </tr>
                            <tr>
                                <td><strong>{{ trans('server.index.disk_space') }}</strong></td>
                                <td>{{ $server->disk }} MB</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="console">
            <div class="row">
                <div class="col-md-12">
                    <textarea id="live_console" class="form-control console" readonly="readonly">Loading Previous Content...</textarea>
                </div>
                <div class="col-md-6">
                    <hr />
                    <form action="#" method="post" id="console_command">
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
                </div>
                <div class="col-md-6" style="text-align:center;">
                    <hr />
                    <button class="btn btn-success btn-sm start disabled" id="server_start">Start</button>
                    <button class="btn btn-primary btn-sm restart disabled" id="server_restart">Restart</button>
                    <button class="btn btn-danger btn-sm stop disabled" id="server_stop">Stop</button>
                    <button class="btn btn-danger btn-sm stop disabled" id="kill_proc"><i class="fa fa-ban" data-toggle="tooltip" data-placement="top" title="Kill Running Process"></i></button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pauseConsole" id="pause_console"><small><i class="fa fa-pause fa-fw"></i></small></button>
                    <div id="pw_resp" style="display:none;margin-top: 15px;"></div>
                </div>
            </div>
        </div>
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
<script>
$(window).load(function () {
    $('[data-toggle="tooltip"]').tooltip();

    // Socket Recieves New Server Stats
    socket.on('stats', function (data) {
        var currentTime = new Date();
        memoryChart.addData([parseInt(data.data.memory / (1024 * 1024))], '');
        memoryChart.removeData();
        if({{ $server->cpu }} > 0) { cpuChart.addData([(data.data.cpu / {{ $server->cpu }}) * 100], ''); }else{ cpuChart.addData([data.data.cpu], ''); }
        cpuChart.removeData();
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
                url: 'https://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/log/750',
                timeout: 10000
            }).done(function(data) {
                $('#live_console').val(data);
                $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to load initial server log.').fadeIn().delay(5000).fadeOut();
            });
        } else {
            $('#live_console').val('');
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

    // -----------------+
    // Charting Methods |
    // -----------------+
    var ctx = $('#memoryChart').get(0).getContext('2d');
    var cty = $('#cpuChart').get(0).getContext('2d');
    var memoryChartData = {labels:["","","","","","","","","","","","","","","","","","","",""],datasets:[{fillColor:"#ccc",strokeColor:"rgba(0,0,0,0)",highlightFill:"#666",data:[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]}]};
    var cpuChartData = {labels:["","","","","","","","","","","","","","","","","","","",""],datasets:[{fillColor:"#ccc",strokeColor:"rgba(0,0,0,0)",highlightFill:"#666",data:[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]}]};
    var memoryChart= new Chart(ctx).Bar(memoryChartData,{animation:!1,showScale:!0,barShowStroke:!1,scaleOverride:!1,tooltipTemplate:"<%= value %> Mb",barValueSpacing:1,barStrokeWidth:1,scaleShowGridLines:!1});
    var cpuChart = new Chart(cty).Bar(cpuChartData,{animation:!1,showScale:!0,barShowStroke:!1,scaleOverride:!1,tooltipTemplate:"<%= value %> %",barValueSpacing:1,barStrokeWidth:1,scaleShowGridLines:!1});
    function updatePlayerListVisibility(data) {
        // Server is On or Starting
        if(data == 1 || data == 3) {
            $('#stats_players').show();
        } else {
            $('#stats_players').hide();
        }
    }
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
                url: 'https://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/console',
                timeout: 10000,
                data: { command: ccmd }
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

            // Server is On or Starting
            if(data == 1 || data == 3) {
                $('#server_start').addClass('disabled');
                $('#server_stop, #server_restart').removeClass('disabled');
            } else {
                $('#server_start').removeClass('disabled');
                $('#server_stop, #server_restart').addClass('disabled');
            }

            if(data !== 0) {
                $('#kill_proc').removeClass('disabled');
            } else {
                $('#kill_proc').addClass('disabled');
            }

        }

        // Kill Server Process Button
        $('#kill_proc').click(function (e){

            e.preventDefault();
            var killConfirm = confirm('WARNING: This operation will not save your server data gracefully. You should only use this if your server is failing to respond to stops.');

            if(killConfirm) {
                $.ajax({
                    type: 'GET',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    url: 'https://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power/kill',
                    timeout: 10000
                }).done(function(data) {
                    $('#pw_resp').attr('class', 'alert alert-success').html('Server has been killed successfully.').fadeIn().delay(5000).fadeOut();
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to process your request. Please try again. ('+ errorThrown +')').fadeIn().delay(5000).fadeOut();
                });
            }

        });

        $('#server_stop').click(function (e){
            e.preventDefault();
            if(can_run) {
                can_run = false;
                $(this).append(' <i class=\'fa fa-refresh fa-spin\'></i>').toggleClass('disabled');
                $.ajax({
                    type: 'GET',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    url: 'https://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power/off',
                    timeout: 10000
                }).done(function(data) {
                    $('#pw_resp').attr('class', 'alert alert-success').html('Server is now stopping...').fadeIn().delay(5000).fadeOut();
                    $('#server_stop').removeClass('disabled').html('Stop');
                    can_run = true;
                    return false;
                }).fail(function(jqXHR, textStatus, errorThrown) {
                        $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to process your request. Please try again. ('+ errorThrown +')').fadeIn().delay(5000).fadeOut();
                        $('#server_stop').removeClass('disabled').html('Stop');
                        can_run = true;
                });
            }
        });

        $('#server_restart').click(function(e){
            e.preventDefault();
            if(can_run) {
                can_run = false;
                $(this).append(' <i class=\'fa fa-refresh fa-spin\'></i>').toggleClass('disabled');
                $.ajax({
                    type: 'GET',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    url: 'https://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power/restart',
                    timeout: 10000
                }).done(function(data) {
                    $('#pw_resp').attr('class', 'alert alert-success').html('Server is now restarting...').fadeIn().delay(5000).fadeOut();
                    $('#server_restart').removeClass('disabled').html('Restart');
                    can_run = true;
                    return false;
                }).fail(function(jqXHR, textStatus, errorThrown) {
                        $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to process your request. Please try again. ('+ errorThrown +')').fadeIn().delay(5000).fadeOut();
                        $('#server_restart').removeClass('disabled').html('Restart');
                        can_run = true;
                });
            }
        });
        $('#server_start').click(function(e){
            e.preventDefault();
            start_server();
        });
        function start_server() {
            if(can_run === true){
                can_run = false;
                $('#server_start').append(' <i class=\'fa fa-refresh fa-spin\'></i>').toggleClass('disabled');
                $.ajax({
                    type: 'GET',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    url: 'https://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power/on',
                    timeout: 10000
                }).done(function(data) {
                    $('#live_console').val('Server is starting...\n');
                    $('#pw_resp').attr('class', 'alert alert-success').html('Server is now starting...').fadeIn().delay(5000).fadeOut();
                    $('#server_start').toggleClass('disabled');
                    $('#server_start').html('Start');
                    can_run = true;
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to process your request. Please try again. ('+ errorThrown +')').fadeIn().delay(5000).fadeOut();
                    $('#server_start').removeClass('disabled');
                    $('#server_start').html('Start');
                    can_run = true;
                });
            }
        }
    @endcan
});

$(document).ready(function () {
    $('.server-index').addClass('active');
});
</script>
@endsection
