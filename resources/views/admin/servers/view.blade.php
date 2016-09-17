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
    Managing Server: {{ $server->name }} ({{ $server->uuidShort}})
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/servers">Servers</a></li>
        <li class="active">{{ $server->name }} ({{ $server->uuidShort}})</li>
    </ul>
    @if($server->suspended === 1)
    <div class="alert alert-warning">
        This server is suspended and has no user access. Processes cannot be started and files cannot be modified. All API access is disabled unless using a master token.
    </div>
    @endif
    @if($server->installed === 0)
        <div class="alert alert-warning">
            This server is still running through the install process and is not avaliable for use just yet. This message will disappear once this process is completed.
        </div>
    @elseif($server->installed === 2)
        <div class="alert alert-danger">
            This server <strong>failed</strong> to install properly. You should delete it and try to create it again or check the daemon logs.
        </div>
    @endif
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#tab_about" data-toggle="tab">About</a></li>
        @if($server->installed === 1)
            <li><a href="#tab_details" data-toggle="tab">Details</a></li>
            <li><a href="#tab_build" data-toggle="tab">Build Configuration</a></li>
            <li><a href="#tab_startup" data-toggle="tab">Startup</a></li>
            <li><a href="#tab_database" data-toggle="tab">Database</a></li>
        @endif
        @if($server->installed !== 2)
            <li><a href="#tab_manage" data-toggle="tab">Manage</a></li>
        @endif
        <li><a href="#tab_delete" data-toggle="tab">Delete</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="tab_about">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <table class="table table-striped" style="margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <td>UUID</td>
                                <td>{{ $server->uuid }}</td>
                            </tr>
                            <tr>
                                <td>Owner</td>
                                <td><a href="{{ route('admin.users.view', $server->owner) }}">{{ $server->a_ownerEmail }}</a></td>
                            </tr>
                            <tr>
                                <td>Location</td>
                                <td><a href="{{ route('admin.locations') }}">{{ $server->a_locationName }}</a></td>
                            </tr>
                            <tr>
                                <td>Node</td>
                                <td><a href="{{ route('admin.nodes.view', $server->node) }}">{{ $server->a_nodeName }}</a></td>
                            </tr>
                            <tr>
                                <td>Service</td>
                                <td>{{ $server->a_serviceName }} :: {{ $server->a_servceOptionName }}</td>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td>{{ $server->name }}</td>
                            </tr>
                            <tr>
                                <td>Memory</td>
                                <td><code>{{ $server->memory }}MB</code> / <code data-toggle="tooltip" data-placement="top" title="Swap Space">{{ $server->swap }}MB</code></td>
                            </tr>
                            <tr>
                                <td><abbr title="Out of Memory">OOM</abbr> Killer</td>
                                <td>{!! ($server->oom_disabled === 0) ? '<span class="label label-success">Enabled</span>' : '<span class="label label-default">Disabled</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Disk Space</td>
                                <td><code>{{ $server->disk }}MB</code></td>
                            </tr>
                            <tr>
                                <td>Block IO Weight</td>
                                <td><code>{{ $server->io }}</code></td>
                            </tr>
                            <tr>
                                <td>CPU Limit</td>
                                <td><code>{{ $server->cpu }}%</code></td>
                            </tr>
                            <tr>
                                <td>Default Connection</td>
                                <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                            </tr>
                            <tr>
                                <td>Connection Alias</td>
                                <td>
                                    @if(!is_null($server->ip_alias))
                                        <code>{{ $server->ip_alias }}:{{ $server->port }}</code>
                                    @else
                                        <span class="label label-default">No Alias Assigned</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Installed</td>
                                <td>{!! ($server->installed === 1) ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>' !!}</td>
                            </tr>
                            <tr>
                                <td>Suspended</td>
                                <td>{!! ($server->suspended === 1) ? '<span class="label label-warning">Suspended</span>' : '<span class="label label-success">No</span>' !!}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($server->installed === 1)
            <div class="tab-pane" id="tab_details">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <form method="POST" action="/admin/servers/view/{{ $server->id }}/details">
                            <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                                <label for="name" class="control-label">Server Name</label>
                                <div>
                                    <input type="text" name="name" value="{{ old('name', $server->name) }}" class="form-control" />
                                    <p class="text-muted"><small>Character limits: <code>a-zA-Z0-9_-</code> and <code>[Space]</code> (max 35 characters).</small></p>
                                </div>
                            </div>
                            <div class="form-group {{ $errors->has('owner') ? 'has-error' : '' }}">
                                <label for="name" class="control-label">Server Owner</label>
                                <div>
                                    <input type="text" name="owner" value="{{ old('owner', $server->a_ownerEmail) }}" class="form-control" />
                                    <p class="text-muted"><small>You can change the owner of this server by changing this field to an email matching another use on this system. If you do this a new daemon security token will be generated automatically.</small></p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="name" class="control-label">Daemon Secret Token</label>
                                <div>
                                    <input type="text" disabled value="{{ $server->daemonSecret }}" class="form-control" />
                                    <p class="text-muted"><small>This token should not be shared with anyone as it has full control over this server.</small></p>
                                </div>
                            </div>
                            <div class="form-group">
                                <div>
                                    <input type="checkbox" name="reset_token"/> Yes, Reset Daemon Token
                                    <p class="text-muted"><small>Resetting this token will cause any requests using the old token to fail.</small></p>
                                </div>
                            </div>
                            <div class="form-group">
                                {!! csrf_field() !!}
                                <input type="submit" class="btn btn-sm btn-primary" value="Update Details" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="tab_build">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    After editing any of the options below you will need to restart the server for changes to take effect. If the server is currently off, you just need to start it and the container will be rebuilt with the new settings.
                                </div>
                            </div>
                        </div>
                        <form action="/admin/servers/view/{{ $server->id }}/build" method="POST">
                            <div class="row">
                                <div class="col-md-6 form-group {{ $errors->has('memory') ? 'has-error' : '' }}">
                                    <label for="memory" class="control-label">Allocated Memory</label>
                                    <div class="input-group">
                                        <input type="text" name="memory" class="form-control" value="{{ old('memory', $server->memory) }}"/>
                                        <span class="input-group-addon">MB</span>
                                    </div>
                                </div>
                                <div class="col-md-6 form-group {{ $errors->has('swap') ? 'has-error' : '' }}">
                                    <label for="swap" class="control-label">Allocated Swap</label>
                                    <div class="input-group">
                                        <input type="text" name="swap" class="form-control" value="{{ old('swap', $server->swap) }}"/>
                                        <span class="input-group-addon">MB</span>
                                    </div>
                                    <p class="text-muted"><small>Setting this to <code>0</code> will disable swap space on this server.</small></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group {{ $errors->has('cpu') ? 'has-error' : '' }}">
                                    <label for="cpu" class="control-label">CPU Limit</label>
                                    <div class="input-group">
                                        <input type="text" name="cpu" class="form-control" value="{{ old('cpu', $server->cpu) }}"/>
                                        <span class="input-group-addon">%</span>
                                    </div>
                                    <p class="text-muted"><small>Each <em>physical</em> core on the system is considered to be <code>100%</code>. Setting this value to <code>0</code> will allow a server to use CPU time without restrictions.</small></p>
                                </div>
                                <div class="col-md-6 form-group {{ $errors->has('io') ? 'has-error' : '' }}">
                                    <label for="io" class="control-label">Block IO Proportion</label>
                                    <div>
                                        <input type="text" name="io" class="form-control" value="{{ old('io', $server->io) }}"/>
                                    </div>
                                    <p class="text-muted"><small>Changing this value can have negative effects on all containers on the system. We strongly recommend leaving this value as <code>500</code>.</small></p>
                                </div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        Additional IPs and Ports can be assigned to this server for use by plugins or other software. The game port is what will show up for the user to use to connect to thier server, and what their configuration files will be forced to use for binding.
                                    </div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="default" class="control-label">Game Port</label>
                                    @foreach ($assigned as $assignment)
                                        <div class="input-group" style="margin:5px auto;">
                                            <span class="input-group-addon">
                                                <input type="radio" @if($assignment->ip == $server->ip && $assignment->port == $server->port) checked="checked" @endif name="default" value="{{ $assignment->ip }}:{{ $assignment->port }}"/>
                                            </span>
                                            <input type="text" class="form-control" value="@if(!is_null($assignment->ip_alias)){{ $assignment->ip_alias }}@else{{ $assignment->ip }}@endif:{{ $assignment->port }} @if(!is_null($assignment->ip_alias))(alias of {{ $assignment->ip }})@endif" readonly />
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-12 form-group">
                                            <label for="add_additional" class="control-label">Assign Additional Ports</label>
                                            <div>
                                                <select name="add_additional[]" class="form-control" multiple>
                                                    @foreach ($unassigned as $assignment)
                                                        <option value="{{ $assignment->ip }}:{{ $assignment->port }}">@if(!is_null($assignment->ip_alias)){{ $assignment->ip_alias }}@else{{ $assignment->ip }}@endif:{{ $assignment->port }} @if(!is_null($assignment->ip_alias))(alias of {{ $assignment->ip }})@endif</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <p class="text-muted"><small>Please note that due to software limitations you cannot assign identical ports on different IPs to the same server. For example, you <strong>cannot</strong> assign both <code>192.168.0.5:25565</code> and <code>192.168.10.5:25565</code> to the same server.</small></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 form-group">
                                            <label for="remove_additional" class="control-label">Remove Additional Ports</label>
                                            <div>
                                                <select name="remove_additional[]" class="form-control" multiple>
                                                    @foreach ($assigned as $assignment)
                                                        <option value="{{ $assignment->ip }}:{{ $assignment->port }}">@if(!is_null($assignment->ip_alias)){{ $assignment->ip_alias }}@else{{ $assignment->ip }}@endif:{{ $assignment->port }} @if(!is_null($assignment->ip_alias))(alias of {{ $assignment->ip }})@endif</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <p class="text-muted"><small>Simply select which ports you would like to remove from the list above. If you want to assign a port on a different IP that is already in use you can select it above and delete it down here.</small></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    {!! csrf_field() !!}
                                    <input type="submit" class="btn btn-sm btn-primary" value="Update Build Configuration" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="tab_startup">
                <form action="{{ route('admin.servers.post.startup', $server->id) }}" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">Changing any of the values below will require a restart for them to take effect.</div>
                                    <label class="control-label">Server Startup Command</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">{{ $server->a_serviceExecutable }}</span>
                                        <input type="text" class="form-control" name="startup" value="{{ old('startup', $server->startup) }}" />
                                    </div>
                                    <p class="text-muted"><small>The following data replacers are avaliable for the startup command: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, and <code>@{{SERVER_PORT}}</code>. They will be replaced with the allocated memory, server ip, and server port respectively.</small></p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                        <div class="panel-body">
                            <div class="row">
                                @foreach($startup as $item)
                                    <div class="form-group col-md-6">
                                        <label class="control-label">
                                            @if($item->required === 1)<span class="label label-primary">Required</span> @endif
                                            @if($item->user_viewable === 0)<span data-toggle="tooltip" data-placement="top" title="Not Visible to Users" class="label label-danger"><i class="fa fa-eye-slash"></i></span> @endif
                                            @if($item->user_editable === 0)<span data-toggle="tooltip" data-placement="top" title="Not Editable by Users" class="label label-danger"><i class="fa fa-edit"></i></span> @endif
                                            {{ $item->name }}
                                        </label>
                                        <div>
                                            <input type="text" name="{{ $item->env_variable }}" class="form-control" value="{{ old($item->env_variable, $item->a_serverValue) }}" data-action="matchRegex" data-regex="{{ $item->regex }}" />
                                        </div>
                                        <p class="text-muted"><small>{!! $item->description !!}<br />Regex: <code>{{ $item->regex }}</code><br />Access as: <code>&#123;&#123;{{$item->env_variable}}&#125;&#125;</code></small></p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    {!! csrf_field() !!}
                                    <input type="submit" class="btn btn-primary btn-sm" value="Update Startup Arguments" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="tab-pane" id="tab_database">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <h4 class="nopad">Add New Database</h4>
                        <form action="{{ route('admin.servers.database', $server->id) }}" method="post">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="control-label">Database Name:</label>
                                    <div class="input-group">
                                        <div class="input-group-addon">{{ $server->uuidShort }}_</div>
                                        <input type="text" name="database" value="{{ old('database') }}" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">Connections From:</label>
                                    <div>
                                        <input type="text" name="remote" value="{{ old('remote', '%') }}" class="form-control">
                                    </div>
                                    <p class="text-muted"><small>Which IP to allow connections from. Standard MySQL wildcard notation allowed (e.g. <code>192.168.%.%</code>).</small></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label class="control-label">Database Server:</label>
                                    <select name="db_server" class="form-control">
                                        @foreach($db_servers as $dbs)
                                            <option value="{{ $dbs->id }}" @if($dbs->linked_node === $server->node)selected="selected"@endif>{{ $dbs->name }} ({{ $dbs->host }}:{{ $dbs->port }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xs-6">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        {!! csrf_field() !!}
                                        <input type="submit" value="Create New Database &rarr;" class="btn btn-sm btn-primary pull-right">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    @if(count($databases) > 0)
                        <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                        <div class="panel-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Database</th>
                                        <th>User (Connections From)</th>
                                        <th>Password</th>
                                        <th>DB Server</th>
                                        <th></th>
                                    </th>
                                </thead>
                                <tbody>
                                    @foreach($databases as $database)
                                        <tr>
                                            <td>{{ $database->database }}</td>
                                            <td>{{ $database->username }} ({{ $database->remote }})</td>
                                            <td><code>{{ Crypt::decrypt($database->password) }}</code> <a href="#" data-action="reset-database-password" data-id="{{ $database->id }}"><i class="fa fa-refresh pull-right"></i></a></td>
                                            <td><code>{{ $database->a_host }}:{{ $database->a_port }}</code></td>
                                            <td class="text-center"><a href="#delete" data-action="delete_database" data-database="{{ $database->id }}" class="text-danger"><i class="fa fa-trash-o"></i></a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        @if($server->installed !== 2)
            <div class="tab-pane" id="tab_manage">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <a href="/server/{{ $server->uuidShort }}/" target="_blank">
                                    <button type="submit" class="btn btn-sm btn-primary">Manage Server</button>
                                </a>
                            </div>
                            <div class="col-md-8">
                                <p>This will take you to the server management page that users normally see and allow you to manage server files as well as check the console and data usage.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <form action="/admin/servers/view/{{ $server->id }}/installed" method="POST">
                                    {!! csrf_field() !!}
                                    <button type="submit" class="btn btn-sm btn-primary">Toggle Install Status</button>
                                </form>
                            </div>
                            <div class="col-md-8">
                                <p>This will toggle the install status for the server.</p>
                                <div class="alert alert-warning">If you have just created this server it is ill advised to perform this action as the daemon will contact the panel when finished which could cause the install status to be wrongly set.</div>
                            </div>
                        </div>
                    </div>
                    @if($server->installed === 1)
                        <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <form action="/admin/servers/view/{{ $server->id }}/rebuild" method="POST">
                                        {!! csrf_field() !!}
                                        <button type="submit" class="btn btn-sm btn-primary">Rebuild Server Container</button>
                                    </form>
                                </div>
                                <div class="col-md-8">
                                    <p>This will trigger a rebuild of the server container when it next starts up. This is useful if you modified the server configuration file manually, or something just didn't work out correctly.</p>
                                    <div class="alert alert-info">A rebuild will automatically occur whenever you edit build configuration settings for the server.</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            @if($server->suspended === 0)
                                <div class="col-md-4 text-center">
                                    <form action="/admin/servers/view/{{ $server->id }}/suspend" method="POST">
                                        {!! csrf_field() !!}
                                        <button type="submit" class="btn btn-sm btn-warning">Suspend Server</button>
                                    </form>
                                </div>
                                <div class="col-md-8">
                                    <p>This will suspend the server, stop any running processes, and immediately block the user from being able to access their files or otherwise manage the server through the panel or API.</p>
                                </div>
                            @else
                                <div class="col-md-4 text-center">
                                    <form action="/admin/servers/view/{{ $server->id }}/unsuspend" method="POST">
                                        {!! csrf_field() !!}
                                        <button type="submit" class="btn btn-sm btn-success">Unsuspend Server</button>
                                    </form>
                                </div>
                                <div class="col-md-8">
                                    <p>This will unsuspend the server and restore normal user access.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="tab-pane" id="tab_delete">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                @if($server->installed === 1)
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <form action="/admin/servers/view/{{ $server->id }}" method="POST" data-attr="deleteServer">
                                    {!! csrf_field() !!}
                                    {!! method_field('DELETE') !!}
                                    <button type="submit" class="btn btn-sm btn-danger">Delete Server</button>
                                </form>
                            </div>
                            <div class="col-md-8">
                                <div class="alert alert-danger">Deleting a server is an irreversible action. <strong>All data will be immediately removed relating to this server.</strong></div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                @endif
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <form action="/admin/servers/view/{{ $server->id }}/force" method="POST" data-attr="deleteServer">
                                {!! csrf_field() !!}
                                {!! method_field('DELETE') !!}
                                <button type="submit" class="btn btn-sm btn-danger">Force Delete Server</button>
                            </form>
                        </div>
                        <div class="col-md-8">
                            <div class="alert alert-danger">This is the same as deleting a server, however, if an error is returned by the daemon it is ignored and the server is still removed from the panel.</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $('#sidebar_links').find("a[href='/admin/servers']").addClass('active');
    $('input[name="default"]').on('change', function (event) {
        $('select[name="remove_additional[]"]').find('option:disabled').prop('disabled', false);
        $('select[name="remove_additional[]"]').find('option[value="' + $(this).val() + '"]').prop('disabled', true).prop('selected', false);
    });
    $('[data-action="matchRegex"]').keyup(function (event) {
        if (!$(this).data('regex')) return;
        var input = $(this).val();
        var regex = new RegExp(escapeRegExp($(this).data('regex')));
        console.log(regex);
        if (!regex.test(input)) {
            $(this).parent().parent().removeClass('has-success').addClass('has-error');
        } else {
            $(this).parent().parent().removeClass('has-error').addClass('has-success');
        }
    });
    $('form[data-attr="deleteServer"]').submit(function (event) {
        event.preventDefault();
        swal({
            title: '',
            type: 'warning',
            text: 'Are you sure that you want to delete this server? There is no going back, all data will immediately be removed.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            event.target.submit();
        });
    });
    $('[data-action="delete_database"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            title: '',
            type: 'warning',
            text: 'Are you sure that you want to delete this database? There is no going back, all data will immediately be removed.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('admin.databases') }}/delete/' + self.data('database'),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function () {
                self.parent().parent().slideUp();
                swal({
                    title: '',
                    type: 'success',
                    text: 'Successfully deleted this database.'
                });
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'An error occured while processing this request.'
                });
            });
        });
    });
    $('[data-action="reset-database-password"]').click(function (e) {
        e.preventDefault();
        var block = $(this);
        $(this).find('i').addClass('fa-spin');
        $.ajax({
            type: 'POST',
            url: '{{ route('server.ajax.reset-database-password', $server->uuidShort) }}',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                'database': $(this).data('id')
            }
        }).done(function (data) {
            block.parent().find('code').html(data);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error(jqXHR);
            var error = 'An error occured while trying to process this request.';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                text: error
            });
        }).always(function () {
            block.find('i').removeClass('fa-spin');
        });
    });
});
</script>
@endsection
