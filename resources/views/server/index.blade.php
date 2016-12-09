{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.master')

@section('title')
    Viewing Server: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::css('css/jquery.terminal.css') !!}
    {!! Theme::js('js/jquery.mousewheel-min.js') !!}
    {!! Theme::js('js/jquery.terminal-0.11.22.min.js') !!}
    {!! Theme::js('js/unix_formatting.js') !!}
    {!! Theme::js('js/vendor/chartjs/chart.min.js') !!}
    {!! Theme::js('js/vendor/jquery/jquery-dateFormat.min.js') !!}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#console" data-toggle="tab">{{ trans('server.index.control') }}</a></li>
        <li><a href="#resources" data-toggle="tab">Resource Use</a></li>
        @can('view-allocation', $server)<li><a href="#allocation" data-toggle="tab">{{ trans('server.index.allocation') }}</a></li>@endcan
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="console">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info hidden" id="consoleThrottled">
                                The console is currently being throttled due to the speed at which data is being sent. Messages are being queued and will appear as the queue is worked through.
                            </div>
                            <div id="terminal">
                            </div>
                        </div>
                        <div class="col-md-12" style="text-align:center;">
                            <hr />
                            @can('power-start', $server)<button class="btn btn-success btn-sm disabled" data-attr="power" data-action="start">Start</button>@endcan
                            @can('power-restart', $server)<button class="btn btn-primary btn-sm disabled" data-attr="power" data-action="restart">Restart</button>@endcan
                            @can('power-stop', $server)<button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="stop">Stop</button>@endcan
                            @can('power-kill', $server)<button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="kill"><i class="fa fa-ban" data-toggle="tooltip" data-placement="top" title="Kill Running Process"></i></button>@endcan
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pauseConsole" id="pause_console"><small><i class="fa fa-pause fa-fw"></i></small></button>
                            <div id="pw_resp" style="display:none;margin-top: 15px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="resources">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <canvas id="chart_memory" style="max-height:300px;"></canvas>
                        </div>
                    </div>
                    <div class="row" style="margin-top:15px;">
                        <div class="col-md-12">
                            <canvas id="chart_cpu" style="max-height:300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('view-allocation', $server)
            <div class="tab-pane" id="allocation">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="alert alert-info">Below is a listing of all avaliable IPs and Ports for your service. To change the default connection address for your server, simply click on the one you would like to make default below.</div>
                        <table class="table table-hover">
                            <tr>
                                <th>IP Address</th>
                                <th>Alias</th>
                                <th>Port</th>
                                <th></th>
                            </tr>
                            @foreach ($allocations as $allocation)
                                <tr>
                                    <td>
                                        <code>{{ $allocation->ip }}</code>
                                    </td>
                                    <td @if(is_null($allocation->ip_alias))class="muted"@endif>
                                        @if(is_null($allocation->ip_alias))
                                            <span class="label label-default">none</span>
                                        @else
                                            <code>{{ $allocation->ip_alias }}</code>
                                        @endif
                                    </td>
                                    <td><code>{{ $allocation->port }}</code></td>
                                    <td class="col-xs-2">
                                        @if($allocation->id === $server->allocation)
                                            <span class="label label-primary is-primary" data-allocation="{{ $allocation->id }}">Primary</span>
                                        @else
                                            <span class="label label-success muted muted-hover use-pointer" data-action="set-connection" data-allocation="{{ $allocation->id }}">Make Primary</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
<div class="modal fade" id="pauseConsole" tabindex="-1" role="dialog" aria-labelledby="PauseConsole" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="PauseConsole">ScrollStop&trade;</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="paused_console" style="height: 300px; overflow-x: scroll;"></div>
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
    <script src="{{ route('server.js', [$server->uuidShort, 'minecraft', 'eula.js']) }}"></script>
@endif
<script>
$(window).load(function () {
    $('[data-toggle="tooltip"]').tooltip();
    var initialStatusSent = false;
    var currentStatus = 0;

    var terminal = $('#terminal').terminal(function (command, term) {
        @can('power-start', $server)
            if (currentStatus === 0 && (command === 'start' || command === 'boot')) {
                powerToggleServer('start');
            }
        @endcan
        @can('send-command', $server)
            if (currentStatus === 0 && !(command === 'start' || command === 'boot')) {
                term.error('Server is currently off, type `start` or `boot` to start server.');
            }
            if (currentStatus !== 0 && command !== '') {
                $.ajax({
                    type: 'POST',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    contentType: 'application/json; charset=utf-8',
                    url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/command',
                    timeout: 10000,
                    data: JSON.stringify({ command: command })
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    var error = 'An error occured while trying to process this request.';
                    if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                        error = jqXHR.responseJSON.error;
                    }
                    term.error(error);
                });
            }
        @else
            term.error('You do not have permission to send commands to this server.');
        @endcan
    }, {
        greetings: '',
        name: '{{ $server->uuid }}',
        height: 400,
        exit: false,
        prompt: '{{ $server->username }}:~$ ',
        scrollOnEcho: false,
        onBlur: function (terminal) {
            return false;
        }
    });

    var ctc = $('#chart_cpu');
    var timeLabels = [];
    var cpuData = [];
    var CPUChart = new Chart(ctc, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [
                {
                    label: "Percent Use",
                    fill: false,
                    lineTension: 0.03,
                    backgroundColor: "#00A1CB",
                    borderColor: "#00A1CB",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
                    pointHoverBorderColor: "rgba(220,220,220,1)",
                    pointHoverBorderWidth: 2,
                    pointRadius: 1,
                    pointHitRadius: 10,
                    data: cpuData,
                    spanGaps: false,
                }
            ]
        },
        options: {
            title: {
                display: true,
                text: 'CPU Usage (as Percent Total)'
            },
            legend: {
                display: false,
            },
            animation: {
                duration: 1,
            }
        }
    });

    var ctm = $('#chart_memory');
    var memoryData = [];
    var MemoryChart = new Chart(ctm, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [
                {
                    label: "Memory Use",
                    fill: false,
                    lineTension: 0.03,
                    backgroundColor: "#01A4A4",
                    borderColor: "#01A4A4",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
                    pointHoverBorderColor: "rgba(220,220,220,1)",
                    pointHoverBorderWidth: 2,
                    pointRadius: 1,
                    pointHitRadius: 10,
                    data: memoryData,
                    spanGaps: false,
                }
            ]
        },
        options: {
            title: {
                display: true,
                text: 'Memory Usage (in Megabytes)'
            },
            legend: {
                display: false,
            },
            animation: {
                duration: 1,
            }
        }
    });
    socket.on('proc', function (proc) {
        if (cpuData.length > 10) {
            cpuData.shift();
            memoryData.shift();
            timeLabels.shift();
        }

        var cpuUse = ({{ $server->cpu }} > 0) ? parseFloat(((proc.data.cpu.total / {{ $server->cpu }}) * 100).toFixed(3).toString()) : proc.data.cpu.total;
        cpuData.push(cpuUse);
        memoryData.push(parseInt(proc.data.memory.total / (1024 * 1024)));

        var m = new Date();
        timeLabels.push($.format.date(new Date(), 'HH:mm:ss'));

        CPUChart.update();
        MemoryChart.update();
    });

    // Socket Recieves New Query
    socket.on('query', function (data){
        // Disabled.
    });

    // New Console Data Recieved
    var outputQueue = [];
    socket.on('console', function (data) {
        outputQueue.push(data.line);
    });

    window.setInterval(pushOutputQueue, {{ env('CONSOLE_PUSH_FREQ', 250) }});
    function pushOutputQueue()
    {
        if (outputQueue.length > {{ env('CONSOLE_PUSH_COUNT', 10) }}) {
            $('#consoleThrottled').removeClass('hidden');
        } else {
            $('#consoleThrottled').addClass('hidden');
        }

        for (var i = 0; i < {{ env('CONSOLE_PUSH_COUNT', 10) }} && outputQueue.length > 0; i++)
        {
            terminal.echo(outputQueue[0]);
            outputQueue.shift();
        }
    }

    // Update Listings on Initial Status
    socket.on('initial_status', function (data) {
        currentStatus = data.status;
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
                terminal.echo(data);
            }).fail(function() {
                terminal.error('Unable to load initial server log, try reloading the page.');
            });
        }
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Update Listings on Status
    socket.on('status', function (data) {
        currentStatus = data.status;
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Load Paused Console with Live Console Data
    $('#pause_console').click(function(){
        $('#paused_console').html($('#terminal').html());
    });

    function updatePlayerListVisibility(data) {
        // Server is On or Starting
        if(data !== 0) {
            $('#stats_players').show();
        } else {
            $('#stats_players').hide();
        }
    }

    @can('set-allocation', $server)
        // Send Request
        function handleChange() {
            $('[data-action="set-connection"]').click(function (event) {
                event.preventDefault();
                var element = $(this);

                $.ajax({
                    method: 'POST',
                    url: '/server/{{ $server->uuidShort }}/ajax/set-primary',
                    data: {
                        allocation: element.data('allocation')
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).done(function (data) {
                    swal({
                        type: 'success',
                        title: '',
                        text: data
                    });
                    element.parents().eq(2).find('.is-primary').addClass('muted muted-hover label-success use-pointer').attr('data-action', 'set-connection').data('action', 'set-connection').removeClass('label-primary is-primary').html('Make Primary');
                    element.removeClass('muted muted-hover label-success use-pointer').attr('data-action', 'do-nothing').data('action', 'do-nothing').addClass('label-primary is-primary').html('Primary');
                    handleChange();
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    var respError;
                    if (typeof jqXHR.responseJSON.error === 'undefined' || jqXHR.responseJSON.error === '') {
                        respError = 'An error occured while attempting to perform this action.';
                    } else {
                        respError = jqXHR.responseJSON.error;
                    }
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        text: respError
                    });
                });
            });
        }
        handleChange();
    @endcan

    var can_run = true;
    function updateServerPowerControls (data) {
        // Server is On or Starting
        if(data == 1 || data == 2) {
            $('[data-attr="power"][data-action="start"]').addClass('disabled');
            $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').removeClass('disabled');
        } else {
            if (data == 0) {
                $('[data-attr="power"][data-action="start"]').removeClass('disabled');
            }
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
        var killConfirm = false;
        if (action === 'kill') {
            swal({
                type: 'warning',
                title: '',
                text: 'This operation will not save your server data gracefully. You should only use this if your server is failing to respond to normal stop commands.',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: true,
                confirmButtonText: 'Kill Server',
                confirmButtonColor: '#d9534f'
            }, function () {
                setTimeout(function() {
                    powerToggleServer('kill');
                }, 100);
            });
        } else {
            powerToggleServer(action);
        }

    });

    function powerToggleServer(action) {
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
            var error = 'An error occured while trying to process this request.';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                text: error
            });
        });
    }
});

$(document).ready(function () {
    $('.server-index').addClass('active');
});
</script>
@endsection
