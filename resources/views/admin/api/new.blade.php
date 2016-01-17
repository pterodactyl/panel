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
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users"> <strong>GET /users</strong>
                        <p class="text-muted"><small>Allows listing of all users currently on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.post"> <strong>POST /users</strong>
                        <p class="text-muted"><small>Allows creating a new user on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.view"> <strong>GET /users/{id}</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific user including active services.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.patch"> <strong>PATCH /users/{id}</strong>
                        <p class="text-muted"><small>Allows modifying user details (email, password, TOTP information).</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.users.delete"> <strong>DELETE /users/{id}</strong>
                        <p class="text-muted"><small>Allows deleting a user.</small><p>
                    </label>
                </div>
            </div>
            <div class="col-md-6 fuelux">
                <h4>Server Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers"> <strong>GET /servers</strong>
                        <p class="text-muted"><small>Allows listing of all servers currently on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.post"> <strong>POST /servers</strong>
                        <p class="text-muted"><small>Allows creating a new server on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.view"> <strong>GET /servers/{id}</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific server.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.suspend"> <strong>POST /servers/{id}/suspend</strong>
                        <p class="text-muted"><small>Allows suspending a server instance.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.unsuspend"> <strong>POST /servers/{id}/unsuspend</strong>
                        <p class="text-muted"><small>Allows unsuspending a server instance.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.servers.delete"> <strong>DELETE /servers/{id}</strong>
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
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes"> <strong>GET /nodes</strong>
                        <p class="text-muted"><small>Allows listing of all nodes currently on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.post"> <strong>POST /nodes</strong>
                        <p class="text-muted"><small>Allows creating a new node on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.view"> <strong>GET /nodes/{id}</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific node including active services.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.view_allocations"> <strong>GET /nodes/{id}/allocations</strong>
                        <p class="text-muted"><small>Allows viewing details about a specific node including active services.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.nodes.delete"> <strong>DELETE /nodes/{id}</strong>
                        <p class="text-muted"><small>Allows deleting a node.</small><p>
                    </label>
                </div>
            </div>
            <div class="col-md-6 fuelux">
                <h4>Service Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.services"> <strong>GET /services</strong>
                        <p class="text-muted"><small>Allows listing of all services configured on the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.services.view"> <strong>GET /services/{id}</strong>
                        <p class="text-muted"><small>Allows listing details about each service on the system including service options and variables.</small><p>
                    </label>
                </div>
                <h4>Location Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" value="api.locations"> <strong>GET /locations</strong>
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
