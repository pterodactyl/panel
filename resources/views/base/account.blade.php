@extends('layouts.master')

@section('title', 'Your Account')

@section('sidebar-server')
@endsection

@section('content')
<div class="col-md-9">
    @if (count($errors) > 0)
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {{ trans('base.form_error') }}
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ $message }}
            </div>
        @endforeach
    @endforeach
	<div class="row">
		<div class="col-md-6">
			<h3 class="nopad">{{ trans('base.account.update_pass') }}</h3><hr />
				<form action="/account/password" method="post">
					<div class="form-group">
						<label for="current_password" class="control-label">{{ trans('strings.current_password') }}</label>
						<div>
							<input type="password" class="form-control" name="current_password" />
						</div>
					</div>
					<div class="form-group">
						<label for="new_password" class="control-label">{{ trans('base.account.new_password') }}</label>
						<div>
							<input type="password" class="form-control" name="new_password" />
                            <p class="text-muted"><small>{{ trans('base.password_req') }}</small></p>
						</div>
					</div>
					<div class="form-group">
						<label for="new_password_again" class="control-label">{{ trans('base.account.new_password') }} {{ trans('strings.again') }}</label>
						<div>
							<input type="password" class="form-control" name="new_password_confirmation" />
						</div>
					</div>
					<div class="form-group">
						<div>
							{!! csrf_field() !!}
							<input type="submit" class="btn btn-primary btn-sm" value="{{ trans('base.account.update_pass') }}" />
						</div>
					</div>
				</form>
		</div>
		<div class="col-md-6">
			<h3 class="nopad">{{ trans('base.account.update_email') }}</h3><hr />
				<form action="/account/email" method="post">
					<div class="form-group">
						<label for="new_email" class="control-label">{{ trans('base.account.new_email') }}</label>
						<div>
							<input type="text" class="form-control" name="new_email" />
						</div>
					</div>
					<div class="form-group">
						<label for="password" class="control-label">{{ trans('strings.current_password') }}</label>
						<div>
							<input type="password" class="form-control" name="password" />
						</div>
					</div>
					<div class="form-group">
						<div>
							{!! csrf_field() !!}
							<input type="submit" class="btn btn-primary btn-sm" value="{{ trans('base.account.update_email') }}" />
						</div>
					</div>
				</form>
		</div>
	</div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find('a[href=\'/account\']').addClass('active');
});
</script>
@endsection
