@extends('layouts.admin')

@section('title')
    Games | {{ $category->title }}
@endsection

@section('content-header')
    <h1>{{ $category->title }} <small>Edit Game</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.categories.games.categories') }}">Categories</a></li>
        <li><a href="{{ route('admin.shop.categories.games', $category->id) }}">{{ $category->title }}</a></li>
        <li class="active">Edit Game</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="post" action="{{ route('admin.shop.categories.games.edit', [$category->id, $game->id]) }}">
            <div class="col-xs-12 col-sm-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">General Details</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $game->name) }}" placeholder="Minecraft #01">
                            </div>
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="hide">Show</label>
                                <select id="hide" name="hide" class="form-control">
                                    <option value="0" {{ old('hide', $game->hide) == 0 ? 'selected' : '' }}>Yes</option>
                                    <option value="1" {{ old('hide', $game->hide) == 1 ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image_url">Image URL</label>
                            <input type="text" id="image_url" name="image_url" class="form-control" value="{{ old('image_url', $game->image_url) }}" placeholder="Image URL">
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="short_url">Short URL</label>
                                <input type="text" id="short_url" name="short_url" class="form-control" value="{{ old('short_url', $game->short_url) }}" placeholder="Short URL">
                            </div>
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="price">Price</label>
                                <div class="input-group">
                                    <input type="number" id="price" name="price" class="form-control" value="{{ old('price', $game->price) }}" step=".01" placeholder="1.00">
                                    <span class="input-group-addon">{{ $currency }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Limits</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="database_limit">Database Limit</label>
                            <input type="number" id="database_limit" name="database_limit" class="form-control" value="{{ old('database_limit', $game->database_limit) }}" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="backup_limit">Backup Limit</label>
                            <input type="number" id="backup_limit" name="backup_limit" class="form-control" value="{{ old('backup_limit', $game->backup_limit) }}" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="allocation_limit">Allocation Limit</label>
                            <input type="number" id="allocation_limit" name="allocation_limit" class="form-control" value="{{ old('allocation_limit', $game->allocation_limit) }}" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Resources</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="memory">Memory</label>
                                <div class="input-group">
                                    <input type="number" id="memory" name="memory" class="form-control" value="{{ old('memory', $game->memory) }}" placeholder="512">
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="disk">Disk</label>
                                <div class="input-group">
                                    <input type="number" id="disk" name="disk" class="form-control" value="{{ old('disk', $game->disk) }}" placeholder="1024">
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="cpu">CPU</label>
                                <div class="input-group">
                                    <input type="number" id="cpu" name="cpu" class="form-control" value="{{ old('cpu', $game->cpu) }}" placeholder="100">
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                            <div class="form-group col-xs-12 col-sm-6">
                                <label for="swap">Swap</label>
                                <div class="input-group">
                                    <input type="number" id="swap" name="swap" class="form-control" value="{{ old('swap', $game->swap) }}" placeholder="0">
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">Deployment</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="egg_id">Egg</label>
                            <select id="egg_id" name="egg_id" class="form-control">
                                @foreach ($eggs as $egg)
                                    <option value="{{ $egg->id }}" {{ old('egg_id', $game->egg_id) == $egg->id ? 'selected' : '' }}>{{ $egg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="node_ids">Available Node(s)</label>
                            <select id="node_ids" name="node_ids[]" class="form-control" multiple>
                                @foreach ($nodes as $node)
                                    <option value="{{ $node->id }}" {{ in_array($node->id, old('node_ids', explode(',', $game->node_ids))) ? 'selected' : '' }}>{{ $node->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12">
                <div class="box box-success">
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <a class="btn btn-default" href="{{ route('admin.shop.categories.games', $category->id) }}">Back</a>
                        <button class="btn btn-success pull-right" type="submit">Update</button>
                    </div>
                </div>
            </div>
        </form>
        <div class="col-xs-12 col-sm-4 col-sm-offset-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Move Game to Another Category</h3>
                </div>
                <form method="post" action="{{ route('admin.shop.categories.games.move', [$category->id, $game->id]) }}">
                    <div class="box-body">
                        <p>Warning! This action will be move the server immediately to the new category.</p>
                        <hr>
                        <div class="form-group">
                            <label for="category_id">New Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                @foreach ($categories as $item)
                                    <option value="{{ $item->id }}" {{ old('category_id', 0) == $item->id ? 'selected' : '' }}>{{ $item->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-danger btn-block">Move</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#egg_id').select2({
            placeholder: '- Select Egg -',
        });

        $('#node_ids').select2({
            placeholder: '- Select Node(s) -',
        });

        $('#category_id').select2({
            placeholder: '- Select New Category -',
        });
    </script>
@endsection