{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

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
    API Management
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/api">API Management</a></li>
        <li class="active">New</li>
    </ul>
    <h3>Add New API Key</h3><hr />
    <form action="{{ route('admin.api.new') }}" method="POST">
        <div class="row">
            <div class="col-md-12 fuelux">
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="*"> <strong>*</strong>
                        <p class="text-muted"><small><span class="label label-danger">Danger</span> Allows performing any action aganist the API.</small><p>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 fuelux">
                <h4>User Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.list"> <strong><span class="label label-default">GET</span> /users</strong>
                        <p class="text-muted"><small>Allows listing of all users currently on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.create"> <strong><span class="label label-default">POST</span> /users</strong>
                        <p class="text-muted"><small>Allows creating a new user on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.view"> <strong><span class="label label-default">GET</span> /users/{id}</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific user including active services.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.update"> <strong><span class="label label-default">PATCH</span> /users/{id}</strong>
                        <p class="text-muted"><small>Allows modifying user details (email, password, TOTP information).</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.delete"> <strong><span class="label label-danger">DELETE</span> /users/{id}</strong>
                        <p class="text-muted"><small>Allows deleting a user.</small><p>
                    </label>
                </div>
            </div>
            <div class="col-md-6 fuelux">
                <h4>Server Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.list"> <strong><span class="label label-default">GET</span> /servers</strong>
                        <p class="text-muted"><small>Allows listing of all servers currently on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.create"> <strong><span class="label label-default">POST</span> /servers</strong>
                        <p class="text-muted"><small>Allows creating a new server on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.view"> <strong><span class="label label-default">GET</span> /servers/{id}</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific server.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.config"> <strong><span class="label label-default">PATCH</span> /servers/{id}/config</strong>
                        <p class="text-muted"><small>Allows modifying server config (name, owner, and access token).</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.build"> <strong><span class="label label-default">PATCH</span> /servers/{id}/build</strong>
                        <p class="text-muted"><small>Allows modifying a server's build parameters such as memory, CPU, and disk space along with assigned and default IPs.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.suspend"> <strong><span class="label label-default">POST</span> /servers/{id}/suspend</strong>
                        <p class="text-muted"><small>Allows suspending a server instance.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.unsuspend"> <strong><span class="label label-default">POST</span> /servers/{id}/unsuspend</strong>
                        <p class="text-muted"><small>Allows unsuspending a server instance.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.delete"> <strong><span class="label label-danger">DELETE</span> /servers/{id}</strong>
                        <p class="text-muted"><small>Allows deleting a server.</small><p>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 fuelux">
                <h4>Node Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.list"> <strong><span class="label label-default">GET</span> /nodes</strong>
                        <p class="text-muted"><small>Allows listing of all nodes currently on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.create"> <strong><span class="label label-default">POST</span> /nodes</strong>
                        <p class="text-muted"><small>Allows creating a new node on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.view"> <strong><span class="label label-default">GET</span> /nodes/{id}</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific node including active services.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.allocations"> <strong><span class="label label-default">GET</span> /nodes/allocations</strong>
                        <p class="text-muted"><small>Allows viewing all allocations on the panel for all nodes.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.delete"> <strong><span class="label label-danger">DELETE</span> /nodes/{id}</strong>
                        <p class="text-muted"><small>Allows deleting a node.</small><p>
                    </label>
                </div>
            </div>
            <div class="col-md-6 fuelux">
                <h4>Service Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.services.list"> <strong><span class="label label-default">GET</span> /services</strong>
                        <p class="text-muted"><small>Allows listing of all services configured on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.services.view"> <strong><span class="label label-default">GET</span> /services/{id}</strong>
                        <p class="text-muted"><small>Allows listing details about each service on the system including service options and variables.</small><p>
                    </label>
                </div>
                <h4>Location Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.locations.list"> <strong><span class="label label-default">GET</span> /locations</strong>
                        <p class="text-muted"><small>Allows listing all locations and thier associated nodes.</small><p>
                    </label>
                </div>
            </div>
        </div>
        <div class="well">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="allowed_ips" class="control-label">Allowed IPs</label>
                    <div>
                        <textarea name="allowed_ips" class="form-control" rows="5">{{ old('allowed_ips') }}</textarea>
                        <p class="text-muted"><small>Enter a line delimitated list of IPs that are allowed to access the API using this key. CIDR notation is allowed. Leave blank to allow any IP.</small></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Create New Key" />
                </div>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/api']").addClass('active');
    $('[data-initialize="checkbox"]').checkbox();
});
</script>
@endsection
