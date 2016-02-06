{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.admin')

@section('title')
    Viewing User
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Controls</a></li>
        <li><a href="/admin/accounts">Accounts</a></li>
        <li class="active">{{ $user->email }}</li>
    </ul>
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
                            <a href="#">
                                <button type="button" class="btn btn-sm btn-danger" data-action="deleteUser" value="{{ trans('base.account.delete_user') }}">{{ trans('base.account.delete_user') }}</button>
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
    $('button[data-action="deleteUser"]').click(function (event) {
        event.preventDefault();
        $.ajax({
            method: 'DELETE',
            url: '/admin/accounts/view/{{ $user->id }}',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).done(function (data) {
            alert('Account was successfully deleted from the system.');
            window.location = '/admin/accounts';
        }).fail(function (jqXHR) {
            console.error(jqXHR);
            alert('An error occured: ' + jqXHR.JSONResponse.error);
        })
    })
});
</script>
@endsection
