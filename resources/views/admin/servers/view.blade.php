@extends('layouts.admin')

@section('title')
    Managing Server: {{ $server->name }} ({{ $server->uuidShort}})
@endsection

@section('content')
<div class="col-md-9">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/servers">Servers</a></li>
        <li class="active">{{ $server->name }} ({{ $server->uuidShort}})</li>
    </ul>
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>{{ trans('strings.whoops') }}!</strong> {{ trans('base.validation_error') }}<br><br>
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
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#tab_about" data-toggle="tab">About</a></li>
        <li><a href="#tab_details" data-toggle="tab">Details</a></li>
        <li><a href="#tab_build" data-toggle="tab">Build Configuration</a></li>
        <li><a href="#tab_startup" data-toggle="tab">Startup</a></li>
        <li><a href="#tab_manage" data-toggle="tab">Manage</a></li>
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
                                <td>{{ $server->a_ownerEmail }}</td>
                            </tr>
                            <tr>
                                <td>Location</td>
                                <td>{{ $server->a_locationName }}</td>
                            </tr>
                            <tr>
                                <td>Node</td>
                                <td>{{ $server->a_nodeName }}</td>
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
                                <td><code>{{ $server->memory }}MB</code> (Swap: {{ $server->swap }}MB) (OOM Killer: <strong>{{ ($server->oom_disabled === 0) ? 'enabled' : 'disabled' }}</strong>)</td>
                            </tr>
                            <tr>
                                <td>Disk Space</td>
                                <td><code>{{ $server->disk }}MB</code> (Enforced: <strong>no</strong>)</td>
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
                                <td>Installed</td>
                                <td>{{ ($server->installed === 1) ? 'Yes' : 'No' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                                        <input type="text" class="form-control" value="{{ $assignment->ip }}:{{ $assignment->port }}" readonly />
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
                                                    <option value="{{ $assignment->ip }}:{{ $assignment->port }}">{{ $assignment->ip }}:{{ $assignment->port }}</option>
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
                                                    <option value="{{ $assignment->ip }}:{{ $assignment->port }}" @if($assignment->ip == $server->ip && $assignment->port == $server->port) disabled @endif>{{ $assignment->ip }}:{{ $assignment->port }}</option>
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
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    Startup
                </div>
            </div>
        </div>
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
                            <p>This will trigger a rebuild of the server container when it next starts up. This is useful if you modified the server configuration file manually, or something just didn't work out correctly. Please be aware: if you manually updated the server's configuration file, you will need to restart the daemon before doing this, or it will be overwritten.</p>
                            <div class="alert alert-info">A rebuild will automatically occur whenever you edit build configuration settings for the server.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_delete">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
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
    $('#sidebar_links').find("a[href='/admin/servers']").addClass('active');
    $('input[name="default"]').on('change', function (event) {
        $('select[name="remove_additional[]"]').find('option:disabled').prop('disabled', false);
        $('select[name="remove_additional[]"]').find('option[value="' + $(this).val() + '"]').prop('disabled', true).prop('selected', false);
    });
    $('form[data-attr="deleteServer"]').submit(function (event) {
        if (confirm('Are you sure that you want to delete this server? There is no going back, all data will immediately be removed.')) {
            event.submit();
        }
    });
});
</script>
@endsection
