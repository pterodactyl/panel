@extends('layouts.admin')

@section('title')
    New Account
@endsection

@section('content')
<div class="col-md-9">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Controls</a></li>
        <li><a href="/admin/accounts">Accounts</a></li>
        <li class="active">Add New Account</li>
	</ul>
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>{{ trans('strings.whoops') }}!</strong> {{ trans('auth.errorencountered') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <h3>Create New Account</h3><hr />
    <form action="new" method="post">
		<fieldset>
			<div class="form-group">
				<label for="username" class="control-label">Username</label>
				<div>
					<input type="text" autocomplete="off" name="username" class="form-control" />
				</div>
			</div>
			<div class="form-group">
				<label for="email" class="control-label">Email</label>
				<div>
					<input type="text" autocomplete="off" name="email" class="form-control" />
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div id="gen_pass" class=" alert alert-success" style="display:none;margin-bottom: 10px;"></div>
				</div>
				<div class="form-group col-md-6">
					<label for="pass" class="control-label">Password</label>
					<div>
						<input type="password" name="password" class="form-control" />
					</div>
				</div>
				<div class="form-group col-md-6">
					<label for="pass_2" class="control-label">Password Again</label>
					<div>
						<input type="password" name="password_confirmation" class="form-control" />
					</div>
				</div>
			</div>
			<div class="form-group">
				<div>
                    {!! csrf_field() !!}
					<button class="btn btn-primary btn-sm" type="submit">Create Account</button>
					<button class="btn btn-default btn-sm" id="gen_pass_bttn" type="button">Generate Password</button>
				</div>
			</div>
		</fieldset>
	</form>
</div>
<script>
$(document).ready(function(){
	$("#sidebar_links").find("a[href='/admin/account/new']").addClass('active');
	$("#gen_pass_bttn").click(function(e){
		e.preventDefault();
		$.ajax({
			type: "GET",
			url: "/password-gen/12",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
           },
			success: function(data) {
				$("#gen_pass").html('<strong>Generated Password:</strong> ' + data).slideDown();
				$('input[name="password"], input[name="password_confirmation"]').val(data);
				return false;
			}
		});
		return false;
	});
});
$(document).ready(function () {
	$('#sidebar_links').find("a[href='/admin/accounts']").addClass('active');
});
</script>
@endsection
