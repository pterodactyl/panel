@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
		<li class="active">Admin Control</li>
	</ul>
    <h3 class="nopad">Pterodactyl Admin Control Panel</h3><hr />
    <p>Welcome to the most advanced, lightweight, and user-friendly open source game server control panel.</p>
</div>
<script>
$(document).ready(function () {
	$('#sidebar_links').find("a[href='/admin']").addClass('active');
});
</script>
@endsection
