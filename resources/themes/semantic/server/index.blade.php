@extends('layouts.master')

@section('title')
    Viewing Server: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::css('css/metricsgraphics.css') !!}
    {!! Theme::js('js/d3.min.js') !!}
    {!! Theme::js('js/metricsgraphics.min.js') !!}
@endsection

@section('content')
    <div class="ui secondary pointing menu">
        <a class="active item" data-tab="overview">Overview</a>
        <a class="item" data-tab="console">{{ trans('server.index.control') }}</a>
        @can('view-allocation', $server)
            <a class="item" data-tab="allocation">{{ trans('server.index.allocation') }}</a>
        @endcan
    </div>
    <div class="ui bottom active tab" data-tab="overview">
        <h2 class="ui dividing header">Online Players</h2>
        <span id="players"></span>
        <h2 class="ui dividing header">Usage Statistics</h2>
        <div class="ui grid">
            <div class="eight wide column">
                <div class="ui progress" id="currentMemoryBar">
                    <div class="bar">
                        @if($server->memory != 0)
                            <div class="progress"></div>
                        @endif
                    </div>
                    <div class="label">Memory Usage: <span id="currentMemory"></span> / @if($server->memory == 0) Unlimited @else {{ $server->memory }} MB @endif</div>
                </div>
            </div>
            <div class="eight wide column">
                <div class="ui progress" id="currentCpuBar">
                    <div class="bar">
                        @if($server->cpu != 0)
                            <div class="progress"></div>
                        @endif
                    </div>
                    <div class="label">CPU Usage: <span id="currentCpu"></span> / @if($server->cpu == 0) Unlimited @else {{ $server->cpu }} @endif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="ui bottom tab" data-tab="console">Console</div>
    <div class="ui bottom tab" data-tab="allocation">allocation</div>
    <script>
        $('.menu .item').tab();
        function updateUsage() {
            $.ajax({
                type: 'GET',
                url: '/server/{{ $server->uuidShort }}/ajax/status',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                $('#currentMemory').html(parseInt(data.proc.memory.total / (1024 * 1024)));
                $('#currentCpu').html(parseInt(data.proc.cpu.total));

                $('#currentMemoryBar').progress({
                    percent: data.proc.memory.total / data.proc.memory.amax * 100
                });

                $('#currentCpuBar').progress({
                    percent:  data.proc.cpu.total / {{ $server->cpu }} * 100
                });

                if(data.proc.memory.amax * 0.9 < data.proc.memory.total) {
                    $('#currentMemoryBar').addClass('red');
                } else if(data.proc.memory.amax * 0.7 < data.proc.memory.total) {
                    $('#currentMemoryBar').addClass('orange');
                } else {
                    $('#currentMemoryBar').addClass('green');
                }

                if({{ $server->cpu }} * 0.9 < data.proc.cpu.total) {
                    $('#currentCpuBar').addClass('red');
                } else if({{ $server->cpu }} * 0.7 <  data.proc.cpu.total) {
                    $('#currentCpuBar').addClass('orange');
                } else {
                    $('#currentCpuBar').addClass('green');
                }
                $('#players').empty();
                if(data.query.players[0]) {
                    $.each(data.query.players, function(index, item) {
                        $('#players').append('<div class="ui label">' + item.name + '</div>');
                    });
                } else {
                    $('#players').append('<p>No players are online.</p>');
                }
                console.log(data);
            }).fail(function (jqXHR) {
                console.error(jqXHR);
            });
        }
        updateUsage();
        setInterval('updateUsage()', 1000);
    </script>
@endsection
