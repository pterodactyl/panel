@extends('layouts.admin')

@section('title')
    Node List
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">Nodes</li>
    </ul>
    <h3>All Nodes</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th class="visible-lg">Location</th>
                <th>FQDN</th>
                <th class="hidden-xs">Memory</th>
                <th class="hidden-xs">Disk</th>
                <th class="text-center hidden-xs">Servers</th>
                <th class="text-center">HTTPS</th>
                <th class="text-center">Public</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($nodes as $node)
                <tr>
                    <td><a href="/admin/nodes/view/{{ $node->id }}">{{ $node->name }}</td>
                    <td class="visible-lg">{{ $node->a_locationName }}</td>
                    <td><code>{{ $node->fqdn }}</code></td>
                    <td class="hidden-xs">{{ $node->memory }} MB</td>
                    <td class="hidden-xs">{{ $node->disk }} MB</td>
                    <td class="text-center hidden-xs">{{ $node->a_serverCount }}</td>
                    <td class="text-center" style="color:{{ ($node->scheme === 'https') ? '#50af51' : '#d9534f' }}"><i class="fa fa-{{ ($node->scheme === 'https') ? 'lock' : 'unlock' }}"></i></td>
                    <td class="text-center"><i class="fa fa-{{ ($node->public === 1) ? 'check' : 'times' }}"></i></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $nodes->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/nodes']").addClass('active');
});
</script>
@endsection
