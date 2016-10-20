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
@extends('layouts.master')

@section('title', 'API Access')

@section('sidebar-server')
@endsection

@section('content')
<div class="col-md-12 fuelux">
    <div class="wizard" data-initialize="wizard" id="apiWizard">
        <div class="steps-container">
            <ul class="steps">
                <li data-step="1" data-name="user" class="active">
                    <span class="badge">1</span>Permissions
                    <span class="chevron"></span>
                </li>
                <li data-step="2" data-name="admin">
                    <span class="badge">2</span>Admin
                    <span class="chevron"></span>
                </li>
                <li data-step="3" data-name="ips">
                    <span class="badge">3</span>Security
                    <span class="chevron"></span>
                </li>
            </ul>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-sm btn-default btn-prev">
                <span class="fa fa-arrow-left"></span>Prev</button>
            <button type="button" class="btn btn-sm btn-primary btn-next" data-last="Complete">Next
                <span class="fa fa-arrow-right"></span>
            </button>
        </div>
        <form action="{{ route('account.api.new') }}" method="POST" id="perms_form">
        <div class="step-content">
            <div class="step-pane active alert" data-step="1">
                <div class="well">Any servers that you are a subuser for will be accessible through this API with the same permissions that you currently have.</div>
                <div class="row">
                    <div class="col-md-6 fuelux">
                        <h4>Base Information</h4><hr />
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="permissions[]" type="checkbox" value="user:*"> <strong>User:*</strong>
                                <p class="text-muted"><small><span class="label label-danger">Danger</span> Allows performing any action aganist the User API.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="permissions[]" type="checkbox" value="user:me"> <strong><span class="label label-default">GET</span> Base Information</strong>
                                <p class="text-muted"><small>Returns a listing of all servers that this account has access to.</small><p>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 fuelux">
                        <h4>Server Management</h4><hr />
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="permissions[]" type="checkbox" value="user:server"> <strong><span class="label label-default">GET</span> Server Info</strong>
                                <p class="text-muted"><small>Allows access to viewing information about a single server including current stats and allocations.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="permissions[]" type="checkbox" value="user:server.power"> <strong><span class="label label-default">PUT</span> Server Power</strong>
                                <p class="text-muted"><small>Allows access to control server power status.</small><p>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane alert" data-step="2">
                <div class="row">
                    <div class="col-md-12 fuelux">
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:*"> <strong>Admin:*</strong>
                                <p class="text-muted"><small><span class="label label-danger">Danger</span> Allows performing any action aganist the Admin API.</small><p>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 fuelux">
                        <h4>User Management</h4><hr />
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:users.list"> <strong><span class="label label-default">GET</span> List Users</strong>
                                <p class="text-muted"><small>Allows listing of all users currently on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:users.create"> <strong><span class="label label-default">POST</span> Create User</strong>
                                <p class="text-muted"><small>Allows creating a new user on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:users.view"> <strong><span class="label label-default">GET</span> List Single User</strong>
                                <p class="text-muted"><small>Allows viewing details about a specific user including active services.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:users.update"> <strong><span class="label label-default">PATCH</span> Update User</strong>
                                <p class="text-muted"><small>Allows modifying user details (email, password, TOTP information).</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:users.delete"> <strong><span class="label label-danger">DELETE</span> Delete User</strong>
                                <p class="text-muted"><small>Allows deleting a user.</small><p>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 fuelux">
                        <h4>Server Management</h4><hr />
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.list"> <strong><span class="label label-default">GET</span> List Servers</strong>
                                <p class="text-muted"><small>Allows listing of all servers currently on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.create"> <strong><span class="label label-default">POST</span> Create Server</strong>
                                <p class="text-muted"><small>Allows creating a new server on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.view"> <strong><span class="label label-default">GET</span> List Single Server</strong>
                                <p class="text-muted"><small><span class="label label-danger">Danger</span> Allows viewing details about a specific server including the <code>daemon_token</code> as current process information.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.config"> <strong><span class="label label-default">PATCH</span> Update Configuration</strong>
                                <p class="text-muted"><small>Allows modifying server config (name, owner, and access token).</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.build"> <strong><span class="label label-default">PATCH</span> Update Build</strong>
                                <p class="text-muted"><small>Allows modifying a server's build parameters such as memory, CPU, and disk space along with assigned and default IPs.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.suspend"> <strong><span class="label label-default">POST</span> Suspend</strong>
                                <p class="text-muted"><small>Allows suspending a server instance.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.unsuspend"> <strong><span class="label label-default">POST</span> Unsuspend</strong>
                                <p class="text-muted"><small>Allows unsuspending a server instance.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:servers.delete"> <strong><span class="label label-danger">DELETE</span> Delete Server</strong>
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
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:nodes.list"> <strong><span class="label label-default">GET</span> List Nodes</strong>
                                <p class="text-muted"><small>Allows listing of all nodes currently on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:nodes.create"> <strong><span class="label label-default">POST</span> Create Node</strong>
                                <p class="text-muted"><small>Allows creating a new node on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:nodes.view"> <strong><span class="label label-default">GET</span> List Single Node</strong>
                                <p class="text-muted"><small>Allows viewing details about a specific node including active services.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:nodes.allocations"> <strong><span class="label label-default">GET</span> List Allocations</strong>
                                <p class="text-muted"><small>Allows viewing all allocations on the panel for all nodes.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:nodes.delete"> <strong><span class="label label-danger">DELETE</span> Delete Node</strong>
                                <p class="text-muted"><small>Allows deleting a node.</small><p>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 fuelux">
                        <h4>Service Management</h4><hr />
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:services.list"> <strong><span class="label label-default">GET</span> List Services</strong>
                                <p class="text-muted"><small>Allows listing of all services configured on the system.</small><p>
                            </label>
                        </div>
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:services.view"> <strong><span class="label label-default">GET</span> List Single Service</strong>
                                <p class="text-muted"><small>Allows listing details about each service on the system including service options and variables.</small><p>
                            </label>
                        </div>
                        <h4>Location Management</h4><hr />
                        <div class="checkbox highlight">
                            <label class="checkbox-custom highlight" data-initialize="checkbox">
                                <input class="sr-only" name="adminPermissions[]" type="checkbox" value="admin:locations.list"> <strong><span class="label label-default">GET</span> List Locations</strong>
                                <p class="text-muted"><small>Allows listing all locations and thier associated nodes.</small><p>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane alert" data-step="3">
                <div class="form-group">
                    <label for="allowed_ips" class="control-label">Descriptive Memo</label>
                    <div>
                        <input type="text" name="memo" class="form-control" value="{{ old('memo') }}" />
                        <p class="text-muted">Enter a breif description of what this API key will be used for.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="allowed_ips" class="control-label">Allowed IPs</label>
                    <div>
                        <textarea name="allowed_ips" class="form-control" rows="5">{{ old('allowed_ips') }}</textarea>
                        <p class="text-muted">Enter a line delimitated list of IPs that are allowed to access the API using this key. CIDR notation is allowed. Leave blank to allow any IP.</p>
                    </div>
                </div>
            </div>
        </div>
        {!! csrf_field() !!}
        </form>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find('a[href="/account/api"]').addClass('active');
    $('#apiWizard').on('finished.fu.wizard', function (evt, data) {
        $('#perms_form').submit();
    });
});
</script>
@endsection
