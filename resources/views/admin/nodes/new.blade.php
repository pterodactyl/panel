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
    Create Node
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/nodes">Nodes</a></li>
        <li class="active">Create New Node</li>
    </ul>
    <h3>Create New Node</h3><hr />
    <form action="/admin/nodes/new" method="POST">
        <div class="well">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="name" class="control-label">Node Name</label>
                    <div>
                        <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name') }}" />
                        <p class="text-muted"><small>Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</small></p>
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="name" class="control-label">Location</label>
                    <div>
                        <select name="location" class="form-control">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ (old('location') === $location->id) ? 'checked' : '' }}>{{ $location->long }} ({{ $location->short }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group col-md-2">
                    <label for="public" class="control-label">Public <sup><a data-toggle="tooltip" data-placement="top" title="Allow automatic allocation to this Node?">?</a></sup></label>
                    <div>
                        <input type="radio" name="public" value="1" {{ (old('public') === '1') ? 'checked' : '' }} id="public_1" checked> <label for="public_1" style="padding-left:5px;">Yes</label><br />
                        <input type="radio" name="public" value="0" {{ (old('public') === '0') ? 'checked' : '' }} id="public_0"> <label for="public_0" style="padding-left:5px;">No</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="well">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="fqdn" class="control-label">Fully Qualified Domain Name</label>
                    <div>
                        <input type="text" autocomplete="off" name="fqdn" class="form-control" value="{{ old('fqdn') }}" />
                    </div>
                    <p class="text-muted"><small>Please enter domain name (e.g <code>node.example.com</code>) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.
                        <a tabindex="0" data-toggle="popover" data-trigger="focus" title="Why do I need a FQDN?" data-content="In order to secure communications between your server and this node we use SSL. We cannot generate a SSL certificate for IP Addresses, and as such you will need to provide a FQDN.">Why?</a>
                    </small></p>
                </div>
                <div class="form-group col-md-6">
                    <label for="scheme" class="control-label">Secure Socket Layer</label>
                    <div class="row" style="padding: 7px 0;">
                        <div class="col-xs-6">
                            <input type="radio" name="scheme" value="https" id="scheme_ssl" checked /> <label for="scheme_ssl" style="padding-left: 5px;">Enable HTTPS/SSL</label>
                        </div>
                        <div class="col-xs-6">
                            <input type="radio" name="scheme" value="http" id="scheme_nossl" /> <label for="scheme_nossl" style="padding-left: 5px;">Disable HTTPS/SSL</label>
                        </div>
                    </div>
                    <p class="text-muted"><small>You should always leave SSL enabled for nodes. Disabling SSL could allow a malicious user to intercept traffic between the panel and the daemon potentially exposing sensitive information.</small></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-6 col-xs-6">
                            <label for="memory" class="control-label">Total Memory</label>
                            <div class="input-group">
                                <input type="text" name="memory" data-multiplicator="true" class="form-control" value="{{ old('memory') }}"/>
                                <span class="input-group-addon">MB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6 col-xs-6">
                            <label for="memory_overallocate" class="control-label">Overallocate</label>
                            <div class="input-group">
                                <input type="text" name="memory_overallocate" data-multiplicator="true" class="form-control" value="{{ old('memory_overallocate', 0) }}"/>
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-muted"><small>Enter the total amount of memory avaliable for new servers. If you would like to allow overallocation of memory enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.</small></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-6 col-xs-6">
                            <label for="disk" class="control-label">Disk Space</label>
                            <div class="input-group">
                                <input type="text" name="disk" class="form-control" value="{{ old('disk') }}"/>
                                <span class="input-group-addon">MB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6 col-xs-6">
                            <label for="disk_overallocate" class="control-label">Overallocate</label>
                            <div class="input-group">
                                <input type="text" name="disk_overallocate" class="form-control" value="{{ old('disk_overallocate', 0) }}"/>
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-muted"><small>Enter the total amount of disk space avaliable for new servers. If you would like to allow overallocation of disk space enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="well">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="daemonBase" class="control-label">Daemon Server File Location</label>
                    <div>
                        <input type="text" name="daemonBase" class="form-control" value="{{ old('daemonBase', '/srv/daemon-data') }}"/>
                    </div>
                    <p class="text-muted"><small>The location at which your server files will be stored. Most users do not need to change this.</small></p>
                </div>
                <div class="form-group col-md-6">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="daemonListen" class="control-label">Daemon Listening Port</label>
                            <div>
                                <input type="text" name="daemonListen" class="form-control" value="{{ old('daemonListen', '8080') }}"/>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="daemonSFTP" class="control-label">Daemon SFTP Port</label>
                            <div>
                                <input type="text" name="daemonSFTP" class="form-control" value="{{ old('daemonSFTP', '2022') }}"/>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted"><small>The daemon runs its own SFTP management container and does not use the SSHd process on the main physical server. <Strong>Do not use the same port that you have assigned for your physcial server's SSH process.</strong></small></p>
                </div>
            </div>
        </div>
        <div class="well">
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Create Node" />
                </div>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/nodes/new']").addClass('active');
    $('[data-toggle="popover"]').popover({
        placement: 'auto'
    });
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endsection
