@extends('layouts.master')

@section('title', '503: Server Temporarily Unavaliable')

@section('content')
<div class="col-md-9">
    <div class="panel panel-danger">
        <div class="panel-heading">
            <h3 class="panel-title">HTTP 503: Temporarily Unavaliable</h3>
        </div>
        <div class="panel-body">
            <p style="margin-bottom:0;">The requested server is still completing the install process. Please check back in a few minutes, you should recieve an email as soon as this process is completed.</p>
			<br /><br />
			<div class="progress progress-striped active">
				<div class="progress-bar progress-bar-danger" style="width: 75%"></div>
			</div>
        </div>
    </div>
    <p style="text-align:center;"><a href="{{ URL::previous() }}">Take me back</a> or <a href="/">go home</a>.</p>
</div>
@endsection
