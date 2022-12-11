@extends('layouts.admin')

@section('title')
    List Nodes
@endsection

@section('scripts')
    @parent
    {!! Theme::css('vendor/fontawesome/animation.min.css') !!}
@endsection

@section('content-header')
    <h1>Nodes<small>All nodes available on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Nodes</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Node List</h3>
                <div class="box-tools search01">
                    <form action="{{ route('admin.nodes') }}" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input('filter.name') }}" placeholder="Search Nodes">
                            <div class="input-group-btn">
                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a href="{{ route('admin.nodes.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Memory</th>
                            <th>Disk</th>
                            <th class="text-center">Servers</th>
                            <th class="text-center">SSL</th>
                            <th class="text-center">Public</th>
                        </tr>
                        @foreach ($nodes as $node)
                            <tr>
                                <td class="text-center text-muted left-icon" data-action="ping" data-secret="{{ $node->getDecryptedKey() }}" data-location="{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/api/system"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                                <td>{!! $node->maintenance_mode ? '<span class="label label-warning"><i class="fa fa-wrench"></i></span> ' : '' !!}<a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td>
                                <td>{{ $node->location->short }}</td>
                                <td>{{ $node->memory }} MiB</td>
                                <td>{{ $node->disk }} MiB</td>
                                <td class="text-center">{{ $node->servers_count }}</td>
                                <td class="text-center" style="color:{{ ($node->scheme === 'https') ? '#50af51' : '#d9534f' }}"><i class="fa fa-{{ ($node->scheme === 'https') ? 'lock' : 'unlock' }}"></i></td>
                                <td class="text-center"><i class="fa fa-{{ ($node->public) ? 'eye' : 'eye-slash' }}"></i></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($nodes->hasPages())
                <div class="box-footer with-border">
                    <div class="col-md-12 text-center">{!! $nodes->appends(['query' => Request::input('query')])->render() !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    (function pingNodes() {
        $('td[data-action="ping"]').each(function(i, element) {
            $.ajax({
                type: 'GET',
                url: $(element).data('location'),
                headers: {
                    'Authorization': 'Bearer ' + $(element).data('secret'),
                },
                timeout: 5000
            }).done(function (data) {
                $(element).find('i').tooltip({
                    title: 'v' + data.version,
                });
                $(element).removeClass('text-muted').find('i').removeClass().addClass('fa fa-fw fa-heartbeat faa-pulse animated').css('color', '#50af51');
            }).fail(function (error) {
                var errorText = 'Error connecting to node! Check browser console for details.';
                try {
                    errorText = error.responseJSON.errors[0].detail || errorText;
                } catch (ex) {}

                $(element).removeClass('text-muted').find('i').removeClass().addClass('fa fa-fw fa-heart-o').css('color', '#d9534f');
                $(element).find('i').tooltip({ title: errorText });
            });
        }).promise().done(function () {
            setTimeout(pingNodes, 10000);
        });
    })();
    </script>
@endsection
