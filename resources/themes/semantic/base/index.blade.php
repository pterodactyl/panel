@extends('layouts.master')
@section('title', 'Your Servers')

@section('content')
    @if (Auth::user()->root_admin == 1)
        <div class="ui info message">{{ trans('base.view_as_admin') }}</div>
    @endif
    <div class="ui left icon fluid input">
        <input type="text" placeholder="Search for a server name, IP, or node." id="search">
        <i class="search icon"></i>
    </div>
    <table class="ui celled padded single line table">
        <thead>
            <tr>
                @if (Auth::user()->root_admin == 1)<th></th>@endif
                <th>{{ trans('base.server_name') }}</th>
                <th>{{ trans('strings.node') }}</th>
                <th>{{ trans('strings.connection') }}</th>
                <th>{{ trans('strings.players') }}</th>
                <th>{{ trans('strings.memory') }}</th>
                <th>{{ trans('strings.cpu') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($servers as $server)
                <tr data-server="{{ $server->uuidShort }}">
                    <td>
                        @if ($server->owner === Auth::user()->id)
                            <i class="ui blue empty circular label"></i>
                        @else
                            <i class="ui green empty circular label"></i>
                        @endif
                    </td>
                    <td><a href="/server/{{ $server->uuidShort }}">{{ $server->name }}</a></td>
                    <td>{{ $server->nodeName }} ({{ $server->a_locationShort }})</td>
                    <td><span data-action="status"><a class="ui grey circular label">Unknown</a></span><code> {{ $server->ip }}:{{ $server->port }}</code></td>
                    <td data-action="players">--</td>
                    <td><span data-action="memory">--</span></td>
                    <td><span data-action="cpu" data-cpumax="{{ $server->cpu }}">--</span>%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <script>
        $('#search').keyup(function() {
            $('#search').parent().addClass('loading');
            if ('' != this.value) {
                var reg = new RegExp(this.value, 'i');
                $('.table tbody').find('tr').each(function() {
                    var $me = $(this);
                    if (!$me.children('td').text().match(reg)) {
                        $me.hide();
                    } else {
                        $me.show();
                    }
                });
            } else {
                $('.table tbody').find('tr').show();
            }
            setTimeout(function() {
              $('#search').parent().removeClass('loading')
            }, 1000);
        });
        var Status = {
            0: 'Off',
            1: 'On',
            2: 'Starting',
            3: 'Stopping'
        };
        $('[data-server]').each(function(index, data) {
            var element = $(this);
            $.ajax({
                type: 'GET',
                url: '/server/' + $(this).attr('data-server') + '/ajax/status',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                element.find('[data-action="players"]').html(data.query.players.length);
                element.find('[data-action="memory"]').html(parseInt(data.proc.memory.total / (1024 * 1024)) + ' MB');

                if(data.proc.memory.amax * 0.9 < data.proc.memory.total) {
                    element.find('[data-action="memory"]').css('color', '#DB2828');
                } else if(data.proc.memory.amax * 0.7 < data.proc.memory.total) {
                    element.find('[data-action="memory"]').css('color', '#F2711C');
                }

                element.find('[data-action="cpu"]').html(data.proc.cpu.total);

                switch(data.status) {
                    case 0:
                        element.find('[data-action="status"]').html('<a class="ui red circular label">Offline</a>');
                        break;
                    case 1:
                        element.find('[data-action="status"]').html('<a class="ui green circular label">Online</a>');
                        break;
                    case 2:
                        element.find('[data-action="status"]').html('<a class="ui teal circular label">Starting</a>');
                        break;
                    case 3:
                        element.find('[data-action="status"]').html('<a class="ui orange circular label">Stopping</a>');
                        break;
                }
            }).fail(function (jqXHR) {
                console.error(jqXHR);
            });
        });
    </script>
@endsection