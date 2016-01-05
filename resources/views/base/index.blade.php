@extends('layouts.master')

@section('title', 'Your Servers')

@section('sidebar-server')
@endsection

@section('content')
<div class="col-md-12">
    @if (Auth::user()->root_admin == 1)
        <div class="alert alert-info">{{ trans('base.view_as_admin') }}</div>
    @endif
    @if (!$servers->isEmpty())
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    @if (Auth::user()->root_admin == 1)
                        <th></th>
                    @endif
                    <th>{{ trans('base.server_name') }}</th>
                    <th>{{ trans('strings.node') }}</th>
                    <th>{{ trans('strings.connection') }}</th>
                    <th class="text-center">{{ trans('strings.players') }}</th>
                    <th class="text-center">{{ trans('strings.memory') }}</th>
                    <th class="text-center">{{ trans('strings.cpu') }}</th>
                    <th class="text-center">{{ trans('strings.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($servers as $server)
                    <tr class="dynUpdate" data-server="{{ $server->uuidShort }}">
                        @if (Auth::user()->root_admin == 1)
                            <td style="width:26px;">
                                @if ($server->owner === Auth::user()->id)
                                    <i class="fa fa-circle" style="color:#008cba;"></i>
                                @else
                                    <i class="fa fa-circle" style="color:#ddd;"></i>
                                @endif
                            </td>
                        @endif
                        <td><a href="/server/{{ $server->uuidShort }}">{{ $server->name }}</a></td>
                        <td>{{ $server->nodeName }} ({{ $server->a_locationShort }})</td>
                        <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                        <td class="text-center" data-action="players">--</td>
                        <td class="text-center"><span data-action="memory">--</span> / {{ $server->memory }} MB</td>
                        <td class="text-center"><span data-action="cpu" data-cpumax="{{ $server->cpu }}">--</span> %</td>
                        <td class="text-center" data-action="status">--</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="row">
            <div class="col-md-12 text-center">{!! $servers->render() !!}</div>
        </div>
    @else
        <div class="alert alert-info">{{ trans('base.no_servers') }}</div>
    @endif
</div>
<script>
$(window).load(function () {
    $('#sidebar_links').find('a[href=\'/\']').addClass('active');
    function updateServerStatus () {
        var Status = {
            0: 'Off',
            1: 'On',
            2: 'Starting',
            3: 'Stopping'
        };
        $('.dynUpdate').each(function (index, data) {
            var element = $(this);
            var serverShortUUID = $(this).data('server');
            $.ajax({
                type: 'GET',
                url: '/server/' + serverShortUUID + '/ajax/status',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                if (typeof data.status === 'undefined') {
                    return;
                }
                element.find('[data-action="status"]').html(Status[data.status]);
                if (data.status !== 0) {
                    var cpuMax = element.find('[data-action="cpu"]').data('cpumax');
                    var currentCpu = data.proc.cpu.total;
                    if (cpuMax !== 0) {
                        currentCpu = parseFloat(((data.proc.cpu.total / cpuMax) * 100).toFixed(2).toString());
                    }
                    element.find('[data-action="memory"]').html(parseInt(data.proc.memory.total / (1024 * 1024)));
                    element.find('[data-action="cpu"]').html(currentCpu);
                    element.find('[data-action="players"]').html(data.query.players.length);
                } else {
                    element.find('[data-action="memory"]').html('--');
                    element.find('[data-action="cpu"]').html('--');
                    element.find('[data-action="players"]').html('--');
                }
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                updateElement.removeClass('fa-circle-o-notch fa-spinner fa-spin');
                updateElement.addClass('fa-question-circle').css({ color: 'rgb(227, 50, 0)' });
            });

        });
    }
    updateServerStatus();
    setInterval(updateServerStatus, 10000);
});
</script>
@endsection
