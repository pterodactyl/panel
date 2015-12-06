@extends('layouts.master')

@section('title', 'Your Servers')

@section('sidebar-server')
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
                    <th>{{ trans('strings.location') }}</th>
                    <th>{{ trans('strings.node') }}</th>
                    <th>{{ trans('strings.connection') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($servers as $server)
                    <tr class="dynUpdate" id="{{ $server->uuidShort }}">
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
                        <td>{{ $server->location }}</td>
                        <td>{{ $server->nodeName }}</td>
                        <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                        <td style="width:26px;"><i class="fa fa-circle-o-notch fa-spinner fa-spin applyUpdate"></i></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-info">{{ trans('base.no_servers') }}</div>
    @endif
</div>
<script>
$(window).load(function () {
    $('#sidebar_links').find('a[href=\'/\']').addClass('active');
    function updateServerStatus () {
        $('.dynUpdate').each(function (index, data) {

            var element = $(this);
            var serverShortUUID = $(this).attr('id');
            var updateElement = $(this).find('.applyUpdate');

            updateElement.removeClass('fa-check-circle fa-times-circle').css({ color: '#000' });
            updateElement.addClass('fa-circle-o-notch fa-spinner fa-spin');

            $.ajax({
                type: 'GET',
                url: '/server/' + serverShortUUID + '/ajax/status',
                timeout: 10000
            }).done(function (data) {

                var selector = (data == 'true') ? 'fa-check-circle' : 'fa-times-circle';
                var selectorColor = (data == 'true') ? 'rgb(83, 179, 12)' : 'rgb(227, 50, 0)';

                updateElement.removeClass('fa-circle-o-notch fa-spinner fa-spin');
                updateElement.addClass(selector).css({ color: selectorColor });

            }).fail(function (jqXHR) {

                updateElement.removeClass('fa-circle-o-notch fa-spinner fa-spin');
                updateElement.addClass('fa-question-circle').css({ color: 'rgb(227, 50, 0)' });

            });

        });
    }
    updateServerStatus();
    setInterval(updateServerStatus, 30000);
});
</script>
@endsection
