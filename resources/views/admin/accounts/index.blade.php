@extends('layouts.admin')

@section('title')
    Account List
@endsection

@section('content')
<div class="col-md-9">
    <ul class="breadcrumb">
		<li><a href="/admin">Admin Control</a></li>
		<li class="active">Accounts</li>
	</ul>
    <h3>All Registered Users</h3><hr />
	<table class="table table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th>Username</th>
				<th>Email</th>
                <th>Account Created</th>
                <th>Account Updated</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($users as $user)
				<tr>
					<td><a href="/admin/accounts/view/{{ $user->id }}">@if($user->username !== null){{ $user->username }}@else[unregistered subuser]@endif</a> @if($user->root_admin === 1)<span class="badge">Administrator</span>@endif</td>
					<td><code>{{ $user->email }}</code></td>
                    <td>{{ $user->created_at }}</td>
                    <td>{{ $user->updated_at }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $users->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
	$('#sidebar_links').find("a[href='/admin/accounts']").addClass('active');
});
</script>
@endsection
