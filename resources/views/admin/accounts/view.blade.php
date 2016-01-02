@extends('layouts.admin')

@section('title')
    Viewing User
@endsection

@section('content')
<div class="col-md-9">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Controls</a></li>
        <li><a href="/admin/accounts">Accounts</a></li>
        <li class="active">{{ $user->email }}</li>
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
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {!! $message !!}
            </div>
        @endforeach
    @endforeach
    <h3>Viewing User: {{ $user->email }}</h3><hr />
    <div class="row">
        <div class="col-md-12">
            <form action="/admin/accounts/update" method="post">
                <div class="col-md-6">
                    <fieldset>
                        <div class="form-group">
                            <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                            <div>
                                <input type="text" name="email" value="{{ $user->email }}" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="registered" class="control-label">{{ trans('strings.registered') }}</label>
                            <div>
                                <input type="text" name="registered" value="{{ $user->created_at }}" readonly="readonly" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="root_admin" class="control-label">{{ trans('strings.root_administrator') }}</label>
                            <div>
                                <select name="root_admin" class="form-control">
                                    <option value="0">{{ trans('strings.no') }}</option>
                                    <option value="1" @if($user->root_admin)selected="selected"@endif>{{ trans('strings.yes') }}</option>
                                </select>
                                <p><small class="text-muted"><em><strong><i class="fa fa-warning"></i></strong> {{ trans('base.root_administrator') }}</em></small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="hidden" name="user" value="{{ $user->id }}">
                            {!! csrf_field() !!}
                            <input type="submit" value="{{ trans('base.account.update_user') }}" class="btn btn-primary btn-sm">
                            <a href="/admin/accounts/delete/{{ $user->id }}">
                                <button id="delete" type="button" class="btn btn-sm btn-danger" value="{{ trans('base.account.delete_user') }}">{{ trans('base.account.delete_user') }}</button>
                            </a>
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-6">
                    <div class="well" style="padding-bottom: 0;">
                        <h4 class="nopad">{{ trans('base.account.update_pass') }}</h5><hr>
                            <div class="alert alert-success" style="display:none;margin-bottom:10px;" id="gen_pass"></div>
                            <div class="form-group">
                                <label for="password" class="control-label">{{ trans('strings.password') }}</label>
                                <div>
                                    <input type="password" id="password" name="password" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation" class="control-label">{{ trans('auth.confirmpassword') }}</label>
                                <div>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="email_user" value="1">{{ trans('base.account.email_password') }}</label>
                                    </div>
                                </div>
                                <button class="btn btn-default btn-sm" id="gen_pass_bttn" type="button">Generate Password</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <h3>Associated Servers</h3><hr>
                @if($servers)
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width:2%;"></th>
                                <th>Server Name</th>
                                <th>Node</th>
                                <th>Connection</th>
                                <th style="width:10%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                                @foreach($servers as $server)
                                    <tr>
                                        <td><a href="/server/{{ $server->uuidShort }}/"><i class="fa fa-tachometer"></i></a></td>
                                        <td><a href="/admin/servers/view/{{ $server->id }}">{{ $server->name }}</a></td>
                                        <td>{{ $server->nodeName }}</td>
                                        <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                                        <td>@if($server->active)<span class="label label-success">Enabled</span>@else<span class="label label-danger">Disabled</span>@endif</td>
                                    </td>
                                @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">There are no servers associated with this account.</div>
                @endif
                <a href="/admin/servers/new?email={{ $user->email }}"><button type="button" class="btn btn-success btn-sm">{{ trans('server.index.add_new') }}</button></a>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    $("#sidebar_links").find("a[href='/admin/accounts']").addClass('active');
    $('#delete').click(function() {
        if(confirm('{{ trans('base.confirm') }}')) {
            $('#delete').load($(this).attr('href'));
        }
    });
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
</script>
@endsection
