{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

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
    Manage Server: {{ $server->name }}
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>{{ $server->uuid }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.servers') }}">Servers</a></li>
        <li class="active">{{ $server->username }}</li>
    </ol>
@endsection

@section('content')
    @if($server->suspended && ! $server->trashed())
        <div class="alert alert-warning">
            This server is suspended and has no user access. Processes cannot be started and files cannot be modified. All API access is disabled unless using a master token.
        </div>
    @elseif($server->trashed())
        <div class="callout callout-danger">
            This server is marked for deletion <strong>{{ Carbon::parse($server->deleted_at)->addMinutes(env('APP_DELETE_MINUTES', 10))->diffForHumans() }}</strong>. If you want to cancel this action simply click the button below.
            <br /><br />
            <form action="{{ route('admin.servers.post.queuedDeletion', $server->id) }}" method="POST">
                <button class="btn btn-sm btn-default" name="cancel" value="1">Cancel Deletion</button>
                <button class="btn btn-sm btn-danger pull-right" name="force_delete" value="1"><strong>Force</strong> Delete</button>
                <button class="btn btn-sm btn-danger pull-right" name="delete" style="margin-right:10px;" value="1">Delete</button>
                {!! csrf_field() !!}
            </form>
        </div>
    @endif
    @if(! $server->installed)
        <div class="alert alert-warning">
            This server is still running through the install process and is not avaliable for use just yet. This message will disappear once this process is completed.
        </div>
    @elseif($server->installed === 2)
        <div class="alert alert-danger">
            This server <strong>failed</strong> to install properly. You should delete it and try to create it again or check the daemon logs.
        </div>
    @endif
    <div class="row">
        <div class="col-xs-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab_about" data-toggle="tab">About</a></li>
                    @if($server->installed)
                        <li><a href="#tab_details" data-toggle="tab">Details</a></li>
                        <li><a href="#tab_build" data-toggle="tab">Build Configuration</a></li>
                        <li><a href="#tab_startup" data-toggle="tab">Startup</a></li>
                        <li><a href="#tab_database" data-toggle="tab">Database</a></li>
                    @endif
                    @if($server->installed !== 2)
                        <li><a href="#tab_manage" data-toggle="tab">Manage</a></li>
                    @endif
                    @if(! $server->trashed())
                        <li class="tab-danger"><a href="#tab_delete" data-toggle="tab">Delete</a></li>
                    @endif
                </ul>
                <div class="tab-content">
                    {{--  Start About --}}
                    <div class="tab-pane active" id="tab_about" style="margin: -10px -10px -30px;">
                        <table class="table table-hover">
                            <tr>
                                <td>UUID</td>
                                <td>{{ $server->uuid }}</td>
                            </tr>
                            <tr>
                                <td>Docker Container ID</td>
                                <td data-attr="container-id"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                            </tr>
                            <tr>
                                <td>Docker User ID</td>
                                <td data-attr="container-user"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                            </tr>
                            <tr>
                                <td>Owner</td>
                                <td><a href="{{ route('admin.users.view', $server->owner_id) }}">{{ $server->user->email }}</a></td>
                            </tr>
                            <tr>
                                <td>Location</td>
                                <td><a href="{{ route('admin.locations') }}">{{ $server->node->location->short }}</a></td>
                            </tr>
                            <tr>
                                <td>Node</td>
                                <td><a href="{{ route('admin.nodes.view', $server->node_id) }}">{{ $server->node->name }}</a></td>
                            </tr>
                            <tr>
                                <td>Service</td>
                                <td>{{ $server->option->service->name }} :: {{ $server->option->name }}</td>
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
                                <td><code>{{ $server->allocation->ip }}:{{ $server->allocation->port }}</code></td>
                            </tr>
                            <tr>
                                <td>Connection Alias</td>
                                <td>
                                    @if($server->allocation->alias !== $server->allocation->ip)
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
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
                        </table>
                    </div>
                    {{--  End About / Start Details --}}
                    @if($server->installed)
                        <div class="tab-pane" id="tab_details">
                            <div class="row">
                                <form class="col-sm-6" action="{{ route('admin.servers.view.details', $server->id) }}" method="POST" style="border-right: 1px solid #f4f4f4;">
                                    <div class="form-group">
                                        <label for="name" class="control-label">Server Name</label>
                                        <input type="text" name="name" value="{{ old('name', $server->name) }}" class="form-control" />
                                        <p class="text-muted small">Character limits: <code>a-zA-Z0-9_-</code> and <code>[Space]</code> (max 35 characters).</p>
                                    </div>
                                    <div class="form-group">
                                        <label for="pUserId" class="control-label">Server Owner</label>
                                        <select name="owner_id" class="form-control" id="pUserId">
                                            <option value="{{ $server->owner_id }}" selected>{{ $server->user->email }}</option>
                                        </select>
                                        {{-- <input type="text" name="owner" value="{{ old('owner', $server->user->email) }}" class="form-control" /> --}}
                                        <p class="text-muted small">You can change the owner of this server by changing this field to an email matching another use on this system. If you do this a new daemon security token will be generated automatically.</p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name" class="control-label">Daemon Secret Token</label>
                                        <input type="text" disabled value="{{ $server->daemonSecret }}" class="form-control" />
                                        <p class="text-muted small">This token should not be shared with anyone as it has full control over this server.</p>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" name="reset_token" id="pResetToken"/> <label for="pResetToken">Reset Daemon Token</label>
                                        <p class="text-muted small">Resetting this token will cause any requests using the old token to fail.</p>
                                    </div>
                                    <div class="box-footer">
                                        {!! csrf_field() !!}
                                        <input type="submit" class="btn btn-sm btn-primary" value="Update Details" />
                                    </div>
                                </form>
                                <form class="col-sm-6" action="{{ route('admin.servers.post.container', $server->id) }}" method="POST">
                                    <div class="form-group">
                                        <label for="name" class="control-label">Docker Container Image</label>
                                        <input type="text" name="docker_image" value="{{ $server->image }}" class="form-control" />
                                        <p class="text-muted small">The docker image to use for this server. The default image for this service and option combination is <code>{{ $server->option->docker_image }}</code>.</p>
                                    </div>
                                    <div class="box-footer">
                                        {!! csrf_field() !!}
                                        <input type="submit" class="btn btn-sm btn-primary" value="Update Docker Container" />
                                    </div>
                                </form>
                            </div>
                        </div>
                        {{--  End Details / Start Build --}}
                        <div class="tab-pane" id="tab_build">
                            <form action="/admin/servers/view/{{ $server->id }}/build" method="POST">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 form-group">
                                        <label for="memory" class="control-label">Allocated Memory</label>
                                        <div class="input-group">
                                            <input type="text" name="memory" data-multiplicator="true" class="form-control" value="{{ old('memory', $server->memory) }}"/>
                                            <span class="input-group-addon">MB</span>
                                        </div>
                                        <p class="text-muted small">The maximum amount of memory allowed for this container.</p>
                                    </div>
                                    <div class="col-md-3 col-sm-6 form-group">
                                        <label for="swap" class="control-label">Allocated Swap</label>
                                        <div class="input-group">
                                            <input type="text" name="swap" data-multiplicator="true" class="form-control" value="{{ old('swap', $server->swap) }}"/>
                                            <span class="input-group-addon">MB</span>
                                        </div>
                                        <p class="text-muted small">Setting this to <code>0</code> will disable swap space on this server. Setting to <code>-1</code> will allow unlimited swap.</p>
                                    </div>
                                    <div class="col-md-3 col-sm-6 form-group">
                                        <label for="cpu" class="control-label">CPU Limit</label>
                                        <div class="input-group">
                                            <input type="text" name="cpu" class="form-control" value="{{ old('cpu', $server->cpu) }}"/>
                                            <span class="input-group-addon">%</span>
                                        </div>
                                        <p class="text-muted small">Each <em>physical</em> core on the system is considered to be <code>100%</code>. Setting this value to <code>0</code> will allow a server to use CPU time without restrictions.</p>
                                    </div>
                                    <div class="col-md-3 col-sm-6 form-group">
                                        <label for="io" class="control-label">Block IO Proportion</label>
                                        <div>
                                            <input type="text" name="io" class="form-control" value="{{ old('io', $server->io) }}"/>
                                        </div>
                                        <p class="text-muted small">Changing this value can have negative effects on all containers on the system. We strongly recommend leaving this value as <code>500</code>.</p>
                                    </div>
                                </div>
                                <hr />
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label for="pAllocation" class="control-label">Game Port</label>
                                        <select id="pAllocation" name="allocation_id" class="form-control">
                                            @foreach ($assigned as $assignment)
                                                <option value="{{ $assignment->id }}"
                                                    @if($assignment->id === $server->allocation_id)
                                                        selected="selected"
                                                    @endif
                                                >{{ $assignment->alias }}:{{ $assignment->port }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-muted small">The default connection address that will be used for this game server.</p>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="pAddAllocations" class="control-label">Assign Additional Ports</label>
                                        <div>
                                            <select name="add_allocations[]" class="form-control" multiple id="pAddAllocations">
                                                @foreach ($unassigned as $assignment)
                                                    <option value="{{ $assignment->id }}">{{ $assignment->alias }}:{{ $assignment->port }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <p class="text-muted small">Please note that due to software limitations you cannot assign identical ports on different IPs to the same server.</p>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="pRemoveAllocations" class="control-label">Remove Additional Ports</label>
                                        <div>
                                            <select name="remove_allocations[]" class="form-control" multiple id="pRemoveAllocations">
                                                @foreach ($assigned as $assignment)
                                                    <option value="{{ $assignment->id }}" @if($server->allocation_id === $assignment->id)disabled @endif>{{ $assignment->alias }}:{{ $assignment->port }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <p class="text-muted small">Simply select which ports you would like to remove from the list above. If you want to assign a port on a different IP that is already in use you can select it from the left and delete it here.</p>
                                    </div>
                                </div>
                                <div class="box-footer">
                                    {!! csrf_field() !!}
                                    <input type="submit" class="btn btn-sm btn-primary" value="Update Build Configuration" />
                                </div>
                            </form>
                        </div>
                        {{--  End Build / Start Startup --}}
                        <div class="tab-pane" id="tab_startup">
                            Startup
                        </div>
                        {{--  End Startup / Start Database --}}
                        <div class="tab-pane" id="tab_database">
                            Database
                        </div>
                    @endif
                    {{--  End Database / Start Manage --}}
                    @if($server->installed !== 2)
                        <div class="tab-pane" id="tab_manage">
                            <div class="row">
                                <div class="col-sm-6 col-md-4 text-center">
                                    <form action="/admin/servers/view/{{ $server->id }}/installed" method="POST">
                                        {!! csrf_field() !!}
                                        <button type="submit" class="btn btn-primary">Toggle Install Status</button>
                                        <p class="text-muted small">This will toggle the install status for the server.</p>
                                    </form>
                                </div>
                                <div class="col-sm-6 col-md-4 text-center">
                                    <form action="/admin/servers/view/{{ $server->id }}/rebuild" method="POST">
                                        {!! csrf_field() !!}
                                        <button type="submit" class="btn btn-primary">Rebuild Server Container</button>
                                        <p class="text-muted small">This will trigger a rebuild of the server container when it next starts up. This is useful if you modified the server configuration file manually, or something just didn't work out correctly.</p>
                                    </form>
                                </div>
                                <div class="col-sm-6 col-md-4 text-center">
                                    @if(! $server->suspended)
                                        <form action="/admin/servers/view/{{ $server->id }}/suspend" method="POST">
                                            {!! csrf_field() !!}
                                            <button type="submit" class="btn btn-warning">Suspend Server</button>
                                            <p class="text-muted small">This will suspend the server, stop any running processes, and immediately block the user from being able to access their files or otherwise manage the server through the panel or API.</p>
                                        </form>
                                    @else
                                        <form action="/admin/servers/view/{{ $server->id }}/unsuspend" method="POST">
                                            {!! csrf_field() !!}
                                            <button type="submit" class="btn btn-success">Unsuspend Server</button>
                                            <p class="text-muted small">This will unsuspend the server and restore normal user access.</p>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    {{--  End Manage / Start Delete --}}
                    @if(! $server->trashed())
                        <div class="tab-pane" id="tab_delete">
                            <div class="row">
                                @if($server->installed)
                                    <div class="col-sm-6">
                                        <form action="/admin/servers/view/{{ $server->id }}" class="text-center" method="POST" data-attr="deleteServer">
                                            {!! csrf_field() !!}
                                            {!! method_field('DELETE') !!}
                                            <button type="submit" class="btn btn-danger">Delete Server</button>
                                        </form>
                                        <p>
                                            <div class="callout callout-danger">
                                                Deleting a server is an irreversible action. <strong>All data will be immediately removed relating to this server.</strong>
                                            </div>
                                        </p>
                                    </div>
                                @endif
                                <div class="col-sm-6">
                                    <form action="/admin/servers/view/{{ $server->id }}/force" class="text-center" method="POST" data-attr="deleteServer">
                                        {!! csrf_field() !!}
                                        {!! method_field('DELETE') !!}
                                        <button type="submit" class="btn btn-danger">Force Delete Server</button>
                                    </form>
                                    <p>
                                        <div class="callout callout-danger">
                                            This is the same as deleting a server, however, if an error is returned by the daemon it is ignored and the server is still removed from the panel.
                                        </div>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    {{--  End Delete --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
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
    $('#pAddAllocations').select2();
    $('#pRemoveAllocations').select2();
    $('#pAllocation').select2();
    $('#pUserId').select2({
        ajax: {
            url: Router.route('admin.users.json'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function (data, params) {
                return { results: data };
            },
            cache: true,
        },
        escapeMarkup: function (markup) { return markup; },
        minimumInputLength: 2,
        templateResult: function (data) {
            if (data.loading) return data.text;

            return '<div class="user-block"> \
                <img class="img-circle img-bordered-xs" src="https://www.gravatar.com/avatar/' + data.md5 + '?s=120" alt="User Image"> \
                <span class="username"> \
                    <a href="#">' + data.name_first + ' ' + data.name_last +'</a> \
                </span> \
                <span class="description"><strong>' + data.email + '</strong> - ' + data.username + '</span> \
            </div>';
        },
        templateSelection: function (data) {
            if (typeof data.name_first === 'undefined') {
                data = {
                    md5: '{{ md5(strtolower($server->user->email)) }}',
                    name_first: '{{ $server->user->name_first }}',
                    name_last: '{{ $server->user->name_last }}',
                    email: '{{ $server->user->email }}',
                    id: {{ $server->owner_id }}
                };
            }

            return '<div> \
                <span> \
                    <img class="img-rounded img-bordered-xs" src="https://www.gravatar.com/avatar/' + data.md5 + '?s=120" style="height:28px;margin-top:-4px;" alt="User Image"> \
                </span> \
                <span style="padding-left:5px;"> \
                    ' + data.name_first + ' ' + data.name_last + ' (<strong>' + data.email + '</strong>) \
                </span> \
            </div>';
        }
    });
    (function checkServerInfo() {
        $.ajax({
            type: 'GET',
            headers: {
                'X-Access-Token': '{{ $server->daemonSecret }}',
                'X-Access-Server': '{{ $server->uuid }}'
            },
            url: '{{ $server->node->scheme }}://{{ $server->node->fqdn }}:{{ $server->node->daemonListen }}/server',
            dataType: 'json',
            timeout: 5000,
        }).done(function (data) {
            $('td[data-attr="container-id"]').html('<code>' + data.container.id + '</code>');
            $('td[data-attr="container-user"]').html('<code>' + data.user + '</code>');
        }).fail(function (jqXHR) {
            $('td[data-attr="container-id"]').html('<code>error</code>');
            $('td[data-attr="container-user"]').html('<code>error</code>');
            console.error(jqXHR);
        }).always(function () {
            setTimeout(checkServerInfo, 60000);
        })
    })();
    </script>
@endsection
