@extends('layouts.admin')

@section('title')
    Location List
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">Locations</li>
    </ul>
    <h3>All Locations</h3><hr />
    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>Location</th>
                <th>Description</th>
                <th class="text-center">Nodes</th>
                <th class="text-center">Servers</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($locations as $location)
                <tr>
                    <td><a href="#/edit/{{ $location->id }}" data-action="edit" data-location="{{ $location->id }}"><code>{{ $location->short }}</code></td>
                    <td>{{ $location->long }}</td>
                    <td class="text-center">{{ $location->a_nodeCount }}</td>
                    <td class="text-center">{{ $location->a_serverCount }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $locations->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/locations']").addClass('active');
});
</script>
@endsection
