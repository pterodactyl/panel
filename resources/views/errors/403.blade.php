@extends('layouts.master')

@section('title', '403: Forbidden')

@section('content')
<div class="col-md-9">
    <div class="panel panel-danger">
        <div class="panel-heading">
            <h3 class="panel-title">HTTP 403: Access Denied</h3>
        </div>
        <div class="panel-body">
            <p style="margin-bottom:0;">You do not have permission to access that function. Please contact your server administrator to request permission.</p>
        </div>
    </div>
    <p style="text-align:center;"><img src="{{ asset('images/403.jpg') }}" /></p>
    <p style="text-align:center;"><a href="{{ URL::previous() }}">Take me back</a> or <a href="/">go home</a>.</p>
</div>
@endsection
