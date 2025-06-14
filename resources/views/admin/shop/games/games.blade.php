@extends('layouts.admin')

@section('title')
    Games | {{ $category->title }}
@endsection

@section('content-header')
    <h1>{{ $category->title }} <small>Manage games in this category.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.categories.games.categories') }}">Categories</a></li>
        <li class="active">{{ $category->title }}</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Game List</h3>
                    <div class="box-tools">
                        <a class="btn btn-sm btn-primary" href="{{ route('admin.shop.categories.games.create', $category->id) }}">Create New</a>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Short URL</th>
                                <th>Memory</th>
                                <th>Disk</th>
                                <th>CPU</th>
                                <th>Swap</th>
                                <th>Price</th>
                                <th>Show</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($games as $game)
                                <tr>
                                    <td>{{ $game->id }}</td>
                                    <td>{{ $game->name }}</td>
                                    <td><code>/{{ $game->short_url }}</code></td>
                                    <td>{{ $game->memory }} MB</td>
                                    <td>{{ $game->disk }} MB</td>
                                    <td>{{ $game->cpu }}%</td>
                                    <td>{{ $game->swap }} MB</td>
                                    <td><label class="label label-primary">{{ $game->price }} {{ $currency }}</label></td>
                                    <td>
                                        @if ($game->hide == 1)
                                            <span class="label label-danger">No</span>
                                        @else
                                            <span class="label label-success">Yes</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a class="btn btn-warning btn-xs" href="{{ $game->image_url }}" target="_blank"><i class="fa fa-image"></i></a>
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.shop.categories.games.edit', [$category->id, $game->id]) }}"><i class="fa fa-pencil"></i></a>
                                        <button class="btn btn-xs btn-danger" data-action="delete" data-id="{{ $game->id }}"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);

            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this game?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.shop.categories.games.delete', $category->id) }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id'),
                    }
                }).done(() => {
                    self.parent().parent().slideUp();

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this game.'
                    });
                }).fail((jqXHR) => {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof jqXHR.responseJSON.errors[0].detail !== 'undefined') ? jqXHR.responseJSON.errors[0].detail : 'A system error has occurred! Please try again later...'
                    });
                });
            });
        });
    </script>
@endsection
