@extends('layouts.admin')

@section('title')
    Manager User: {{ $user->username }}
@endsection

@section('content-header')
    <h1>{{ $user->name_first }} {{ $user->name_last}}<small>{{ $user->username }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.users') }}">Users</a></li>
        <li class="active">{{ $user->username }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <form action="{{ route('admin.users.view', $user->id) }}" method="post">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Identity</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="email" class="control-label">Email</label>
                        <div>
                            <input type="email" name="email" value="{{ $user->email }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registered" class="control-label">Username</label>
                        <div>
                            <input type="text" name="username" value="{{ $user->username }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registered" class="control-label">Client First Name</label>
                        <div>
                            <input type="text" name="name_first" value="{{ $user->name_first }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registered" class="control-label">Client Last Name</label>
                        <div>
                            <input type="text" name="name_last" value="{{ $user->name_last }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Default Language</label>
                        <div>
                            <select name="language" class="form-control">
                                @foreach($languages as $key => $value)
                                    <option value="{{ $key }}" @if($user->language === $key) selected @endif>{{ $value }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted"><small>The default language to use when rendering the Panel for this user.</small></p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    {!! method_field('PATCH') !!}
                    <input type="submit" value="Update User" class="btn btn-primary btn-sm">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Password</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-success" style="display:none;margin-bottom:10px;" id="gen_pass"></div>
                    <div class="form-group no-margin-bottom">
                        <label for="password" class="control-label">Password <span class="field-optional"></span></label>
                        <div>
                            <input type="password" id="password" name="password" class="form-control form-autocomplete-stop">
                            <p class="text-muted small">Leave blank to keep this user's password the same. User will not receive any notification if password is changed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Permissions</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="root_admin" class="control-label">Administrator</label>
                        <div>
                            <select name="root_admin" class="form-control">
                                <option value="0">@lang('strings.no')</option>
                                <option value="1" {{ $user->root_admin ? 'selected="selected"' : '' }}>@lang('strings.yes')</option>
                            </select>
                            <p class="text-muted"><small>Setting this to 'Yes' gives a user full administrative access.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-6">
	        <div class="box">
		        <div class="box-header with-border">
			        <h3 class="box-title">Personal Details</h3>
		        </div>
		        <div class="box-body">
			        <div class="row">
				        <div class="form-group col-xs-12 col-sm-6">
					        <label for="country">Country</label>
					        <select id="country" name="country" class="form-control">
						        @foreach (\Pterodactyl\Classes\Countries::$countries as $countryCode => $country)
							        <option value="{{ $countryCode }}" {{ $countryCode == $user->country ? 'selected' : '' }}>{{ $country }}</option>
						        @endforeach
					        </select>
				        </div>
				        <div class="form-group col-xs-12 col-sm-6">
					        <label for="zip_code">Zip Code</label>
					        <input type="text" id="zip_code" name="zip_code" class="form-control" value="{{ $user->zip_code }}">
				        </div>
			        </div>
			        <div class="row">
				        <div class="form-group col-xs-12 col-sm-8">
					        <label for="address">Address</label>
					        <input type="text" id="address" name="address" class="form-control" value="{{ $user->address }}">
				        </div>
				        <div class="form-group col-xs-12 col-sm-4">
					        <label for="credit">Credit</label>
					        <div class="input-group">
						        <input type="text" id="credit" name="credit" class="form-control" value="{{ $user->credit }}">
						        <span class="input-group-addon">{{ count(\Illuminate\Support\Facades\DB::table('settings')->where('key', '=', 'settings::shop::currency')->get()) < 1 ? 'USD' : \Illuminate\Support\Facades\DB::table('settings')->where('key', '=', 'settings::shop::currency')->get()[0]->value }}</span>
					        </div>
				        </div>
			        </div>
		        </div>
	        </div>
        </div>
    </form>
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Delete User</h3>
            </div>
            <div class="box-body">
                <p class="no-margin">There must be no servers associated with this account in order for it to be deleted.</p>
            </div>
            <div class="box-footer">
                <form action="{{ route('admin.users.view', $user->id) }}" method="POST">
                    {!! csrf_field() !!}
                    {!! method_field('DELETE') !!}
                    <input id="delete" type="submit" class="btn btn-sm btn-danger pull-right" {{ $user->servers->count() < 1 ?: 'disabled' }} value="Delete User" />
                </form>
            </div>
        </div>
    </div>
    <div class="col-xs-12">
	    <div class="box box-info">
		    <div class="box-header with-border">
			    <h3 class="box-title">Associated Servers</h3>
		    </div>
		    <div class="box-body table-responsive no-padding">
    			<table class="table table-hover">
				    <thead>
					    <tr>
						    <th>#</th>
						    <th>Name</th>
						    <th>Node</th>
						    <th>Creation Date</th>
						    <th>Expiration Date</th>
					    </tr>
				    </thead>
				    <tbody>
					    @foreach (\Illuminate\Support\Facades\DB::table('servers')->where('owner_id', '=', $user->id)->leftJoin('nodes', 'nodes.id', '=', 'servers.node_id')->select(['servers.*', 'nodes.name as nodeName'])->get() as $server)
						    <tr>
							    <td>{{ $server->id }}</td>
							    <td><a href="{{ route('admin.servers.view', $server->id) }}" target="_blank">{{ $server->name }}</a></td>
							    <td><a href="{{ route('admin.nodes.view', $server->node_id) }}" target="_blank">{{ $server->nodeName }}</a></td>
							    <td><code>{{ $server->created_at }}</code></td>
							    <td>
								    @if (is_null($server->expired_at))
									    <span class="label label-warning"><i class="fa fa-remove"></i></span>
								    @else
									    <code>{{ $server->expired_at }}</code>
								    @endif
							    </td>
						    </tr>
					    @endforeach
				    </tbody>
			    </table>
		    </div>
	    </div>
    </div>
</div>
@endsection
