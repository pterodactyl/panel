@extends('layouts.admin')

@section('title')
    Games
@endsection

@section('content-header')
    <h1>Games <small>Select category to manage games.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Games</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        @foreach ($categories as $category)
            <div class="col-xs-12 col-sm-3">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $category->title }}</h3>
                    </div>
                    <div class="box-body no-padding">
                       <img src="{{ $category->image_url }}" alt="Category Image" width="100%" height="100%">
                    </div>
                    <div class="box-footer">
                        <a href="{{ route('admin.shop.categories.games', $category->id) }}" class="btn btn-primary btn-block">Manage Games</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
