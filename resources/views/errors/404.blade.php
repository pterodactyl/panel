@extends('layouts.master')

@section('title', '404: Not Found')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('content')
<div class="col-md-8">
    <h1 class="text-center">404 - File Not Found</h1>
    <p class="text-center"><img src="{{ asset('images/404.jpg') }}" /></p>
    <p class="text-center"><a href="{{ URL::previous() }}">Take me back</a> or <a href="/">go home</a>.</p>
</div>
@endsection
