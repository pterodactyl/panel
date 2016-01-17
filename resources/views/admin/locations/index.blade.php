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
                <th class="text-center"></th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($locations as $location)
                <tr>
                    <td><code>{{ $location->short }}</code></td>
                    <td>{{ $location->long }}</td>
                    <td class="text-center">{{ $location->a_nodeCount }}</td>
                    <td class="text-center">{{ $location->a_serverCount }}</td>
                    <td class="text-center"><a href="#edit"><i class="fa fa-wrench" data-action="edit" data-id="{{ $location->id }}" data-short="{{ $location->short }}" data-long="{{ $location->long }}"></i></a></td>
                    <td class="text-center"><a href="#delete" class="text-danger" data-action="delete" data-id="{{ $location->id }}"><i class="fa fa-trash-o"></i></a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $locations->render() !!}</div>
    </div>
    <div class="well">
        <div class="row">
            <div class="col-md-12">
                <button class="btn btn-sm btn-success" id="addNewLocation">Add New Location</button>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/locations']").addClass('active');
    $('[data-action="delete"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            type: 'warning',
            title: '',
            text: 'Do you really want to delete this location?',
            showCancelButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('admin.locations') }}/' + self.data('id'),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function () {
                swal({
                    type: 'success',
                    title: '',
                    text: 'Location was successfully deleted.'
                });
                self.parent().parent().slideUp();
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: (typeof jqXHR.responseJSON !== 'undefined') ? jqXHR.responseJSON.error : 'An error occured while processing this request.'
                });
            });
        });
    });
});
</script>
@endsection
