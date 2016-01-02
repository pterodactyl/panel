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
        <li><a href="#tab_manage" data-toggle="tab">Manage</a></li>
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
                    Build
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_manage">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    Manage
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/servers']").addClass('active');
});
</script>
@endsection
