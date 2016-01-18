@extends('layouts.master')

@section('title')
    Viewing Subusers
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Manage Sub-Users</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Email</th>
                <th>Created</th>
                <th>Modified</th>
                @can('view-subuser', $server)<th></th>@endcan
                @can('delete-subuser', $server)<th></th>@endcan
            </tr>
        </thead>
        <tbody>
            @foreach($subusers as $user)
                <tr>
                    <td><code>{{ $user->a_userEmail }}</code></td>
                    <td>{{ $user->created_at }}</td>
                    <td>{{ $user->updated_at }}</td>
                    @can('view-subuser', $server)<td class="text-center"><a href="{{ route('server.subusers.view', ['server' => $server->uuidShort, 'id' => md5($user->id)]) }}" class="text-success"><i class="fa fa-wrench"></i></a></td>@endcan
                    @can('delete-subuser', $server)<td class="text-center"><a href="#/delete/{{ md5($user->id) }}" class="text-danger"><i class="fa fa-trash-o"></i></a></td>@endcan
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script>
$(document).ready(function () {
    $('.server-users').addClass('active');
});
</script>
@endsection
