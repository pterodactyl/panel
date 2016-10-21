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
    Managing Node: {{ $node->name }}
@endsection

@section('scripts')
    @parent
    {!! Theme::css('css/vendor/metricsgraphics/metricsgraphics.css') !!}
    {!! Theme::js('js/vendor/d3/d3.min.js') !!}
    {!! Theme::js('js/vendor/metricsgraphics/metricsgraphics.min.js') !!}
    {!! Theme::js('js/vendor/socketio/socket.io.min.js') !!}
    {!! Theme::js('js/bootstrap-notify.min.js') !!}
    <script>
        $(document).ready(function () {
            $.notifyDefaults({
                placement: {
                    from: 'bottom',
                    align: 'right'
                },
                newest_on_top: true,
                delay: 2000,
                animate: {
                    enter: 'animated fadeInUp',
                    exit: 'animated fadeOutDown'
                }
            });
        });
    </script>
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/nodes">Nodes</a></li>
        <li class="active">{{ $node->name }}</li>
    </ul>
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#tab_about" data-toggle="tab">About</a></li>
        <li><a href="#tab_settings" data-toggle="tab">Settings</a></li>
        <li><a href="#tab_configuration" data-toggle="tab">Configuration</a></li>
        <li><a href="#tab_allocation" data-toggle="tab">Allocation</a></li>
        <li><a href="#tab_servers" data-toggle="tab">Servers</a></li>
        @if(count($servers) === 0)<li><a href="#tab_delete" data-toggle="tab">Delete</a></li>@endif
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="tab_about">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <table class="table table-striped" style="margin-bottom:0;">
                        <tbody>
                            <tr>
                                <td>Total Servers</td>
                                <td>{{ count($servers) }}</td>
                            </tr>
                            <tr>
                                <td>Memory Allocated</td>
                                <td><strong class="{{ ($stats->memory < ($node->memory * .8)) ? 'text-success' : 'text-danger' }}">{{ is_numeric($stats->memory) ? $stats->memory : 0 }} MB</strong> of
                                    @if(!is_null($node->memory_overallocate))
                                        <abbr data-toggle="tooltip" data-placement="top" title="Allows up to {{ ($node->memory * (1 + ($node->memory_overallocate / 100)) - $node->memory) }} MB over">{{ $node->memory }}</abbr>
                                    @else
                                        {{ $node->memory }}
                                    @endif
                                     MB
                                </td>
                            </tr>
                            <tr>
                                <td>Disk Allocated</td>
                                <td><strong class="{{ ($stats->disk < ($node->disk * .8)) ? 'text-success' : 'text-danger' }}">{{ is_numeric($stats->disk) ? $stats->disk : 0 }} MB</strong> of
                                    @if(!is_null($node->disk_overallocate))
                                        <abbr data-toggle="tooltip" data-placement="top" title="Allows up to {{ ($node->disk * (1 + ($node->disk_overallocate / 100)) - $node->disk) }} MB over">{{ $node->disk }}</abbr>
                                    @else
                                        {{ $node->disk }}
                                    @endif
                                     MB
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-11 text-center" id="chart_memory" style="height:250px;"></div>
                        <div class="col-xs-11 text-center" id="chart_cpu" style="height:250px;"></div>
                        <div class="col-xs-11 text-center" id="chart_players" style="height:250px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_settings">
            <form method="POST" action="/admin/nodes/view/{{ $node->id }}">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="alert alert-warning">
                            Changing some details below may require that you change the configuration file on the node as well as restart the daemon. They have been marked with <span class="label label-warning"><i class="fa fa-power-off"></i></span> below.
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="name" class="control-label">Node Name</label>
                                <div>
                                    <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name', $node->name) }}" />
                                    <p class="text-muted"><small>Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="name" class="control-label">Location</label>
                                <div>
                                    <select name="location" class="form-control">
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ (old('location', $node->location) === $location->id) ? 'checked' : '' }}>{{ $location->long }} ({{ $location->short }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="public" class="control-label">Public <sup><a data-toggle="tooltip" data-placement="top" title="Allow automatic allocation to this Node?">?</a></sup></label>
                                <div>
                                    <input type="radio" name="public" value="1" {{ (old('public', $node->public) === '1') ? 'checked' : '' }} id="public_1" checked> <label for="public_1" style="padding-left:5px;">Yes</label><br />
                                    <input type="radio" name="public" value="0" {{ (old('public', $node->public) === '0') ? 'checked' : '' }} id="public_0"> <label for="public_0" style="padding-left:5px;">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="fqdn" class="control-label">Fully Qualified Domain Name</label>
                                <div>
                                    <input type="text" autocomplete="off" name="fqdn" class="form-control" value="{{ old('fqdn', $node->fqdn) }}" />
                                </div>
                                <p class="text-muted"><small>Please enter domain name (e.g <code>node.example.com</code>) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.
                                    <a tabindex="0" data-toggle="popover" data-trigger="focus" title="Why do I need a FQDN?" data-content="In order to secure communications between your server and this node we use SSL. We cannot generate a SSL certificate for IP Addresses, and as such you will need to provide a FQDN.">Why?</a>
                                </small></p>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="scheme" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Secure Socket Layer</label>
                                <div class="row" style="padding: 7px 0;">
                                    <div class="col-xs-6">
                                        <input type="radio" name="scheme" value="https" id="scheme_ssl" {{ (old('scheme', $node->scheme) === 'https') ? 'checked' : '' }}/> <label for="scheme_ssl" style="padding-left: 5px;">Enable HTTPS/SSL</label>
                                    </div>
                                    <div class="col-xs-6">
                                        <input type="radio" name="scheme" value="http" id="scheme_nossl" {{ (old('scheme', $node->scheme) === 'http') ? 'checked' : '' }}/> <label for="scheme_nossl" style="padding-left: 5px;">Disable HTTPS/SSL</label>
                                    </div>
                                </div>
                                <p class="text-muted"><small>You should always leave SSL enabled for nodes. Disabling SSL could allow a malicious user to intercept traffic between the panel and the daemon potentially exposing sensitive information.</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="memory" class="control-label">Total Memory</label>
                                <div class="input-group">
                                    <input type="text" name="memory" class="form-control" value="{{ old('memory', $node->memory) }}"/>
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="memory_overallocate" class="control-label">Overallocate</label>
                                <div class="input-group">
                                    <input type="text" name="memory_overallocate" class="form-control" value="{{ old('memory_overallocate', $node->memory_overallocate) }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="disk" class="control-label">Disk Space</label>
                                <div class="input-group">
                                    <input type="text" name="disk" class="form-control" value="{{ old('disk', $node->disk) }}"/>
                                    <span class="input-group-addon">MB</span>
                                </div>
                            </div>
                            <div class="form-group col-md-3 col-xs-6">
                                <label for="disk_overallocate" class="control-label">Overallocate</label>
                                <div class="input-group">
                                    <input type="text" name="disk_overallocate" class="form-control" value="{{ old('disk_overallocate', $node->disk_overallocate) }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted"><small>Enter the total amount of disk space and memory avaliable for new servers. If you would like to allow overallocation of disk space or memory enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="daemonListen" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Daemon Port</label>
                                        <div>
                                            <input type="text" name="daemonListen" class="form-control" value="{{ old('daemonListen', $node->daemonListen) }}"/>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="daemonSFTP" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Daemon SFTP Port</label>
                                        <div>
                                            <input type="text" name="daemonSFTP" class="form-control" value="{{ old('daemonSFTP', $node->daemonSFTP) }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="text-muted"><small>The daemon runs its own SFTP management container and does not use the SSHd process on the main physical server. <Strong>Do not use the same port that you have assigned for your physcial server's SSH process.</strong></small></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="reset_secret" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Reset Daemon Key</label>
                                        <div style="padding: 7px 0;">
                                            <input type="checkbox" name="reset_secret" id="reset_secret" /> Reset Daemon Master Key
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <p class="text-muted"><small>Resetting the daemon master key will void any request coming from the old key. This key is used for all sensitive operations on the daemon including server creation and deletion. We suggest changing this key regularly for security.</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                {!! csrf_field() !!}
                                <input type="submit" class="btn btn-sm btn-primary" value="Update Node Information" />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="tab-pane" id="tab_configuration">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        Below is the configuration file for your daemon on this node. We recommend <strong>not</strong> simply copy and pasting the code below unless you know what you are doing. You should run the <code>auto-installer</code> or <code>auto-updater</code> to setup the daemon.
                    </div>
                    <div class="col-md-12">
                        <pre><code>{
    "web": {
        "listen": {{ $node->daemonListen }},
        "ssl": {
            "enabled": {{ $node->scheme === 'https' ? 'true' : 'false' }},
            "certificate": "/etc/letsencrypt/live/{{ $node->fqdn }}/fullchain.pem",
            "key": "/etc/letsencrypt/live/{{ $node->fqdn }}/privkey.pem"
        }
    },
    "docker": {
        "socket": "/var/run/docker.sock",
        "autoupdate_images": true
    },
    "sftp": {
        "path": "{{ $node->daemonBase }}",
        "port": {{ $node->daemonSFTP }},
        "container": "ptdl-sftp"
    },
    "logger": {
        "path": "logs/",
        "src": false,
        "level": "info",
        "period": "1d",
        "count": 3
    },
    "remote": {
        "download": "{{ route('remote.download') }}",
        "installed": "{{ route('remote.install') }}"
    },
    "uploads": {
        "maximumSize": 100000000
    },
    "keys": [
        "{{ $node->daemonSecret }}"
    ]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_allocation">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <h4 style="margin-top:0;">Allocate Additional Ports</h4>
                    <form action="{{ route('admin.nodes.post.allocations', $node->id) }}" method="POST">
                        <div class="row" id="duplicate">
                            <div class="col-md-4 fuelux">
                                <label for="" class="control-label">IP Address or FQDN</label>
                                <div class="input-group input-append dropdown combobox allocationComboBox" data-initialize="combobox">
                                    <input type="text" name="allocate_ip[]" class="form-control pillbox_ip" style="border-right:0;">
                                    <div class="input-group-btn">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            @foreach($allocation_ips as $allocation)
                                                <li data-action="alloc_dropdown_val" data-value="{{ $allocation->ip }}"><a href="#">{{ $allocation->ip }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-7 col-xs-10 fuelux">
                                <label for="" class="control-label">Ports</label>
                                <div class="pillbox allocationPillbox" data-initialize="pillbox">
                                    <ul class="clearfix pill-group">
                                        <li class="pillbox-input-wrap btn-group">
                                            <input type="text" class="form-control dropdown-toggle pillbox-add-item" placeholder="add port">
                                        </li>
                                    </ul>
                                </div>
                                <p class="text-muted"><small>You <strong>must</strong> enter a comma (<code>,</code>) or press the enter key after each port or range that you enter. They should appear in a blue box.</small></p>
                                <input name="allocate_port[]" type="hidden" class="pillboxMain"/>
                            </div>
                            <div class="form-group col-md-1 col-xs-2" style="margin-left: -10px;">
                                <label for="" class="control-label">&nbsp;</label>
                                <button class="btn btn-danger btn-allocate-delete removeClone disabled"><i class="fa fa-close"></i></button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <hr />
                                {!! csrf_field() !!}
                                <input type="submit" class="btn btn-sm btn-primary" value="Add Ports" />
                                <button class="btn btn-success btn-sm cloneElement">Add More Rows</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                <div class="panel-body">
                    <div class="row">
                        <table class="table table-hover" style="margin-bottom:0;">
                            <thead style="font-weight:bold;">
                                <td>IP Address <i class="fa fa-fw fa-minus-square" style="font-weight:normal;color:#d9534f;cursor:pointer;" data-toggle="modal" data-target="#allocationModal"></i></td>
                                <td>IP Alias</td>
                                <td>Port</td>
                                <td>Assigned To</td>
                                <td></td>
                            </thead>
                            <tbody>
                                @foreach($allocations as $allocation)
                                        <tr>
                                        <td class="col-sm-3 align-middle">{{ $allocation->ip }}</td>
                                        <td class="col-sm-3 align-middle">
                                            <input class="form-control input-sm" type="text" value="{{ $allocation->ip_alias }}" data-action="set-alias" data-id="{{ $allocation->id }}" placeholder="none" />
                                            <span class="input-loader"><i class="fa fa-refresh fa-spin fa-fw"></i></span>
                                        </td>
                                        <td class="col-sm-2 align-middle">{{ $allocation->port }}</td>
                                        <td class="col-sm-3 align-middle">@if(!is_null($allocation->assigned_to))<a href="{{ route('admin.servers.view', $allocation->assigned_to) }}">{{ $allocation->assigned_to_name }}</a>@endif</td>
                                        <td class="col-sm-1 align-middle">
                                            @if(is_null($allocation->assigned_to))
                                                <a href="#" data-action="deallocate" data-id="{{ $allocation->id }}"><span class="badge label-danger"><i class="fa fa-trash-o"></i></span></a>
                                            @else
                                                <span class="badge label-default"><i class="fa fa-trash-o"></i></span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="col-md-12 text-center">
                            {{ $allocations->appends(['tab' => 'tab_allocation'])->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="tab_servers">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        The data below is live output from the daemon. CPU usage is displayed relative to the assigned CPU allocation. For example, if a server is assigned <code>10%</code> and the CPU usage below displays <code>90%</code> that means the server is using <code>9%</code> of the total system CPU.
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Owner</th>
                                <th>Service</th>
                                <th class="text-center">Memory</th>
                                <th class="text-center">Disk</th>
                                <th class="text-center">CPU</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servers as $server)
                                <tr data-server="{{ $server->uuid }}">
                                    <td><a href="/admin/servers/view/{{ $server->id }}">{{ $server->name }}</a></td>
                                    <td><a href="/admin/users/view/{{ $server->owner }}"><code>{{ $server->a_ownerEmail }}</a></a></td>
                                    <td>{{ $server->a_serviceName }}</td>
                                    <td class="text-center"><span data-action="memory">--</span> / {{ $server->memory === 0 ? '&infin;' : $server->memory }} MB</td>
                                    <td class="text-center">{{ $server->disk }} MB</td>
                                    <td class="text-center"><span data-action="cpu" data-cpumax="{{ $server->cpu }}">--</span> %</td>
                                    <td class="text-center" data-action="status">--</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-md-12 text-center">{!! $servers->appends(['tab' => 'tab_servers'])->render() !!}</div>
                    </div>
                </div>
            </div>
        </div>
        @if(count($servers) === 0)
            <div class="tab-pane" id="tab_delete">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-4 text-center">
                                <form action="{{ route('admin.nodes.delete', $node->id) }}" method="POST" id="deleteNodeForm">
                                    {!! method_field('DELETE') !!}
                                    {!! csrf_field() !!}
                                    <input type="submit" value="Delete Node" class="btn btn-sm btn-danger" />
                                </form>
                            </div>
                            <div class="col-xs-8">
                                <div class="alert alert-danger" style="margin-bottom:0;">Deleting this node is a permanent action, it cannot be undone.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="row">
        <div class="col-xs-11" id="col11_setter"></div>
    </div>
</div>
<div class="modal fade" id="allocationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Delete Allocations for IP Block</h4>
            </div>
            <form action="{{ route('admin.nodes.view', $node->id) }}/deallocate/block" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <select class="form-control" name="ip">
                                @foreach($allocation_ips as $allocation)
                                    <option value="{{ $allocation->ip }}">{{ $allocation->ip }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {{{ csrf_field() }}}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Delete Allocations</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/nodes']").addClass('active');
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover({
        placement: 'auto'
    });

    $('#deleteNodeForm').submit(function (event) {
        event.preventDefault();
        swal({
            type: 'warning',
            title: 'Are You Sure?',
            text: 'This will immediately delete this node, there is no undo.',
            showCancelButton: true,
            allowOutsideClick: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
        }, function () {
            event.target.submit();
        });
    });

    $('.cloneElement').on('click', function (event) {
        event.preventDefault();
        var rnd = randomKey(10);
        var cloned = $('#duplicate').clone().attr('id', rnd);
        cloned.find('.allocationPillbox').removeClass('allocationPillbox').addClass('allocationPillbox_' + rnd);
        cloned.find('.pillboxMain').removeClass('pillboxMain').addClass('pillbox_' + rnd);
        cloned.find('.removeClone').removeClass('disabled').attr('data-parent', rnd);
        cloned.find('.pillbox_ip').removeClass('pillbox_ip').addClass('pillbox_ip_' + rnd);
        cloned.insertAfter('#duplicate');
        $('.allocationPillbox_' + rnd).pillbox();
        $('.allocationPillbox_' + rnd).on('added.fu.pillbox edited.fu.pillbox removed.fu.pillbox', function pillboxChanged() {
            $('.pillbox_' + rnd).val(JSON.stringify($('.allocationPillbox_' + rnd).pillbox('items')));
        });
        $('.removeClone').unbind().on('click', function (event) {
            event.preventDefault();
            var element = $(this);
            $('#' + element.attr('data-parent')).slideUp(function () {
                element.remove();
                $('.pillbox_' + element.attr('data-parent')).remove();
                $('.pillbox_ip_' + element.attr('data-parent')).remove();
            });
        });
    });

    $('.allocationPillbox').pillbox();
    $('.allocationComboBox').combobox();
    $('.allocationPillbox').on('added.fu.pillbox edited.fu.pillbox removed.fu.pillbox', function pillboxChanged() {
        $('.pillboxMain').val(JSON.stringify($('.allocationPillbox').pillbox('items')));
    });

    var notifySocketError = false;
    var Status = {
        0: 'Off',
        1: 'On',
        2: 'Starting',
        3: 'Stopping'
    };

    // -----------------+
    // Charting Methods |
    // -----------------+
    var memoryGraphSettings = {
        title: 'Memory Usage (MB)',
        data: [{
            'date': new Date(),
            'memory': 0
        }],
        full_width: true,
        full_height: true,
        target: document.getElementById('chart_memory'),
        x_accessor: 'date',
        y_accessor: 'memory',
        y_rug: true,
        area: false,
    };

    var cpuGraphSettings = {
        title: 'CPU Usage (%)',
        data: [{
            'date': new Date(),
            'cpu': 0
        }],
        full_width: true,
        full_height: true,
        target: document.getElementById('chart_cpu'),
        x_accessor: 'date',
        y_accessor: 'cpu',
        y_rug: true,
        area: false,
    };

    var playersGraphSettings = {
        title: 'Players Online',
        data: [{
            'date': new Date(),
            'players': 0
        }],
        full_width: true,
        full_height: true,
        target: document.getElementById('chart_players'),
        x_accessor: 'date',
        y_accessor: 'players',
        y_rug: true,
        area: false,
    };

    MG.data_graphic(memoryGraphSettings);
    MG.data_graphic(cpuGraphSettings);
    MG.data_graphic(playersGraphSettings);

    // Main Socket Object
    var socket = io('{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/stats/', {
        'query': 'token={{ $node->daemonSecret }}'
    });

    // Socket Failed to Connect
    socket.io.on('connect_error', function (err) {
        if(typeof notifySocketError !== 'object') {
            notifySocketError = $.notify({
                message: '{!! trans('server.ajax.socket_error') !!}'
            }, {
                type: 'danger',
                delay: 0
            });
        }
    });

    // Connected to Socket Successfully
    socket.on('connect', function () {
        if (notifySocketError !== false) {
            notifySocketError.close();
            notifySocketError = false;
        }
    });

    socket.on('error', function (err) {
        console.error('There was an error while attemping to connect to the websocket: ' + err + '\n\nPlease try loading this page again.');
    });

    socket.on('live-stats', function (data) {

        if (typeof memoryGraphSettings.data[0][100] !== 'undefined' || memoryGraphSettings.data[0][0].memory === -1) {
            memoryGraphSettings.data[0].shift();
        }
        if (typeof cpuGraphSettings.data[0][100] !== 'undefined' || cpuGraphSettings.data[0][0].cpu === -1) {
            cpuGraphSettings.data[0].shift();
        }
        if (typeof playersGraphSettings.data[0][100] !== 'undefined' || playersGraphSettings.data[0][0].players === -1) {
            playersGraphSettings.data[0].shift();
        }

        memoryGraphSettings.data[0].push({
            'date': new Date(),
            'memory': parseInt(data.stats.memory / (1024 * 1024))
        });

        cpuGraphSettings.data[0].push({
            'date': new Date(),
            'cpu': data.stats.cpu
        });

        playersGraphSettings.data[0].push({
            'date': new Date(),
            'players': data.stats.players
        });

        MG.data_graphic(memoryGraphSettings);
        MG.data_graphic(cpuGraphSettings);
        MG.data_graphic(playersGraphSettings);

        $.each(data.servers, function (uuid, info) {
            var element = $('tr[data-server="' + uuid + '"]');
            element.find('[data-action="status"]').html(Status[info.status]);
            if (info.status !== 0) {
                var cpuMax = element.find('[data-action="cpu"]').data('cpumax');
                var currentCpu = info.proc.cpu.total;
                if (cpuMax !== 0) {
                    currentCpu = parseFloat(((info.proc.cpu.total / cpuMax) * 100).toFixed(2).toString());
                }
                element.find('[data-action="memory"]').html(parseInt(info.proc.memory.total / (1024 * 1024)));
                element.find('[data-action="cpu"]').html(currentCpu);
            } else {
                element.find('[data-action="memory"]').html('--');
                element.find('[data-action="cpu"]').html('--');
            }
        });
    });

    $('span[data-action="delete"]').hover(function() {
        $(this).find('i').css('color', '#d9534f').removeClass('fa-square-o').addClass('fa-minus-square');
    }, function () {
        $(this).find('i').css('color', 'inherit').addClass('fa-square-o').removeClass('fa-minus-square');
    });

    $('a[data-action="deallocate"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var allocation = $(this).data('id');
        swal({
            title: '',
            text: 'Are you sure you want to delete this allocation?',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('admin.nodes.view', $node->id) }}/deallocate/single/' + allocation,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                element.parent().parent().addClass('warning').delay(100).fadeOut();
                swal({
                    type: 'success',
                    title: 'Port Deleted!',
                });
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    title: 'Whoops!',
                    text: jqXHR.responseJSON.error,
                    type: 'error'
                });
            });
        });
    });

    var typingTimer;
    $('input[data-action="set-alias"]').keyup(function () {
        clearTimeout(typingTimer);
        $(this).parent().removeClass('has-error has-success');
        typingTimer = setTimeout(sendAlias, 700, $(this));
    });

    var fadeTimers = [];
    function sendAlias(element) {
        element.parent().find('.input-loader').show();
        clearTimeout(fadeTimers[element.data('id')]);
        $.ajax({
            method: 'POST',
            url: '{{ route('admin.nodes.alias', $node->id) }}',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                alias: element.val(),
                allocation: element.data('id')
            }
        }).done(function (data) {
            element.parent().addClass('has-success');
        }).fail(function (jqXHR) {
            console.error(jqXHR);
            element.parent().addClass('has-error');
        }).always(function () {
            element.parent().find('.input-loader').hide();
            fadeTimers[element.data('id')] = setTimeout(clearHighlight, 2500, element);
        });
    }

    function clearHighlight(element) {
        element.parent().removeClass('has-error has-success');
    }

});
</script>
@endsection
