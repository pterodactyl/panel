{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
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
    New Account
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Controls</a></li>
        <li><a href="/admin/users">Accounts</a></li>
        <li class="active">Add New Account</li>
    </ul>
    <h3>Create New Account</h3><hr />
    <form action="new" method="post">
        <fieldset>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="email" class="control-label">Email</label>
                    <div>
                        <input type="text" autocomplete="off" name="email" value="{{ old('email') }}" class="form-control" />
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="username" class="control-label">Username</label>
                    <div>
                        <input type="text" autocomplete="off" name="username" value="{{ old('username') }}" class="form-control" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="name_first" class="control-label">Client First Name</label>
                    <div>
                        <input type="text" autocomplete="off" name="name_first" value="{{ old('name_first') }}" class="form-control" />
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="name_last" class="control-label">Client Last Name</label>
                    <div>
                        <input type="text" autocomplete="off" name="name_last" value="{{ old('name_last') }}" class="form-control" />
                    </div>
                </div>
                <div class="form-group col-md-12">
                    <label for="root_admin" class="control-label">{{ trans('strings.root_administrator') }}</label>
                    <div>
                        <select name="root_admin" class="form-control">
                            <option value="0">{{ trans('strings.no') }}</option>
                            <option value="1">{{ trans('strings.yes') }}</option>
                        </select>
                        <p class="text-muted"><small>Setting this to 'Yes' gives a user full administrative access.</small></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <hr />
                    <div class="alert alert-info">
                        <p>Providing a user password is optional. New user emails prompt users to create a password the first time they login. If a password is provided here you will need to find a different method of providing it to the user.</p>
                    </div>
                </div>
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
    $('#sidebar_links').find("a[href='/admin/users/new']").addClass('active');
});
</script>
@endsection
