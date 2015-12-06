@extends('layouts.master')

@section('title', '404: Not Found')


@section('right-nav')
@endsection

@section('sidebar')
@endsection

@section('content')
    <h1 style="text-align:center;">404 - File Not Found</h1>
    <p style="text-align:center;"><img src="{{ asset('images/404.jpg') }}" /></p>
    <p style="text-align:center;"><a href="{{ URL::previous() }}">Take me back</a> or <a href="/">go home</a>.</p>
@endsection
