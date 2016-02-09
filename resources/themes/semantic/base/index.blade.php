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
    <table class="ui celled padded table">
        <thead>
            <tr>
                @if (Auth::user()->root_admin == 1)<th></th>@endif
                <th>{{ trans('base.server_name') }}</th>
                <th>{{ trans('strings.node') }}</th>
                <th>{{ trans('strings.connection') }}</th>
                <th>{{ trans('strings.players') }}</th>
                <th>{{ trans('strings.memory') }}</th>
                <th>{{ trans('strings.cpu') }}</th>
                <th>{{ trans('strings.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($servers as $server)
                <tr>
                    <td>
                        @if ($server->owner === Auth::user()->id)
                            <i class="ui blue empty circular label"></i>
                        @else
                            <i class="ui green empty circular label"></i>
                        @endif
                    </td>
                    <td><a href="/server/{{ $server->uuidShort }}">{{ $server->name }}</a></td>
                    <td>{{ $server->nodeName }} ({{ $server->a_locationShort }})</td>
                    <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                    <td>--</td>
                    <td><span data-action="memory">--</span> / {{ $server->memory }} MB</td>
                    <td><span data-action="cpu" data-cpumax="{{ $server->cpu }}">--</span> %</td>
                    <td>--</td>
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
    </script>
@endsection
