@extends('layouts.admin')

@section('title')
    Server List
@endsection

@section('content')
<div class="col-md-9">
    <ul class="breadcrumb">
		<li><a href="/admin">Admin Control</a></li>
		<li class="active">Servers</li>
	</ul>
    <h3>All Servers</h3><hr />
	<table class="table table-bordered table-hover">
		<thead>
			<tr>
				<th>Server Name</th>
                <th>Owner</th>
				<th>Node</th>
                <th>Default Connection</th>
                <th>SFTP Username</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($servers as $server)
				<tr class="dynUpdate @if($server->active !== 1)active @endif" id="{{ $server->uuidShort }}">
					<td><a href="/admin/servers/view/{{ $server->id }}">{{ $server->name }}</td>
                    <td><a href="/admin/users/view/{{ $server->owner }}">{{ $server->a_ownerEmail }}</a></td>
                    <td><a href="/admin/nodes/view/{{ $server->node }}">{{ $server->a_nodeName }}</a></td>
                    <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                    <td><code>{{ $server->username }}</code></td>
				</tr>
			@endforeach
		</tbody>
	</table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $servers->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
	$('#sidebar_links').find("a[href='/admin/servers']").addClass('active');
});
</script>
@endsection
