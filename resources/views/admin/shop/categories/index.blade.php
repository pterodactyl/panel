@extends('layouts.admin')

@section('title')
    Categories
@endsection

@section('content-header')
    <h1>Categories <small>Shop categories.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Categories</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 col-sm-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Category List</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Short URL</th>
                                <th>Image</th>
                                <th>Show</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->title }}</td>
                                    <td><code>/{{ $category->short_url }}</code></td>
                                    <td><a href="{{ $category->image_url }}" target="_blank">{{ $category->image_url }}</a></td>
                                    <td>
                                        @if ($category->hide == 1)
                                            <span class="label label-danger">No</span>
                                        @else
                                            <span class="label label-success">Yes</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.shop.categories.edit', $category->id) }}"><i class="fa fa-pencil"></i></a>
                                        <button class="btn btn-xs btn-danger" data-action="delete" data-id="{{ $category->id }}"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="box box-{{ isset($currentCategory) ? 'warning' : 'success' }}">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ isset($currentCategory) ? 'Edit' : 'Create' }} Category</h3>
                </div>
                <form method="post" action="{{ isset($currentCategory) ? route('admin.shop.categories.edit', $currentCategory->id) : route('admin.shop.categories.create') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" placeholder="Title" class="form-control" value="{{ old('title', (isset($currentCategory) ? $currentCategory->title : '')) }}">
                        </div>
                        <div class="form-group">
                            <label for="short_url">Short URL</label>
                            <input type="text" id="short_url" name="short_url" placeholder="Short URL" class="form-control" value="{{ old('short_url', (isset($currentCategory) ? $currentCategory->short_url : '')) }}">
                        </div>
                        <div class="form-group">
                            <label for="image_url">Image URL</label>
                            <input type="text" id="image_url" name="image_url" placeholder="Image URL" class="form-control" value="{{ old('image_url', (isset($currentCategory) ? $currentCategory->image_url : '')) }}">
                        </div>
                        <div class="form-group">
                            <label for="hide">Show</label>
                            <select id="hide" name="hide" class="form-control">
                                <option value="0" {{ old('hide', (isset($currentCategory) ? $currentCategory->hide : '')) == 0 ? 'selected' : '' }}>Yes</option>
                                <option value="1" {{ old('hide', (isset($currentCategory) ? $currentCategory->hide : '')) == 1 ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        @if (isset($currentCategory))
                            <a href="{{ route('admin.shop.categories') }}" class="btn btn-default">Back</a>
                        @endif
                        <button class="btn btn-success pull-right" type="submit">{{ isset($currentCategory) ? 'Edit' : 'Create' }}</button>
                    </div>
                </form>
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
                text: 'Are you sure you want to delete this category?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.shop.categories.delete') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id'),
                    }
                }).done(() => {
                    self.parent().parent().slideUp();

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this category.'
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
