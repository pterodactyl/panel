@extends('layouts.admin')

@section('title')
    Create New Server
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/servers">Servers</a></li>
        <li class="active">Create New Server</li>
    </ul>
    <h3>Create New Server</h3><hr />
    <form action="/admin/servers/new" method="POST">
        <div class="well">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="name" class="control-label">Server Name</label>
                    <div>
                        <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name') }}" />
                        <p class="text-muted"><small><em>Character limits: <code>a-zA-Z0-9_-</code> and <code>[Space]</code> (max 35 characters)</em></small></p>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="owner" class="control-label">Owner Email</label>
                    <div>
                        <input type="text" autocomplete="off" name="owner" class="form-control" value="{{ old('owner', Input::get('email')) }}" />
                    </div>
                </div>
            </div>
        </div>
        <div id="load_settings">
            <div class="well">
                <div class="row">
                    <div class="ajax_loading_box" style="display:none;"><i class="fa fa-refresh fa-spin ajax_loading_position"></i></div>
                    <div class="form-group col-md-6">
                        <label for="location" class="control-label">Server Location</label>
                        <div>
                            <select name="location" id="getLocation" class="form-control">
                                <option disabled selected> -- Select a Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->long }} ({{ $location->short }})</option>
                                @endforeach
                            </select>
                            <p class="text-muted"><small>The location in which this server will be deployed.</small></p>
                        </div>
                    </div>
                    <div class="form-group col-md-6 hidden">
                        <label for="node" class="control-label">Server Node</label>
                        <div>
                            <select name="node" id="getNode" class="form-control">
                                <option disabled selected> -- Select a Node</option>
                            </select>
                            <p class="text-muted"><small>The node which this server will be deployed to.</small></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6 hidden">
                        <label for="ip" class="control-label">Server IP</label>
                        <div>
                            <select name="ip" id="getIP" class="form-control">
                                <option disabled selected> -- Select an IP</option>
                            </select>
                            <p class="text-muted"><small>Select the main IP that this server will be listening on. You can assign additional open IPs and ports below.</small></p>
                        </div>
                    </div>
                    <div class="form-group col-md-6 hidden">
                        <label for="port" class="control-label">Server Port</label>
                        <div>
                            <select name="port" id="getPort" class="form-control"></select>
                            <p class="text-muted"><small>Select the main port that this server will be listening on.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="well">
            <div class="row">
                <div class="form-group col-md-4 col-xs-4">
                    <label for="memory" class="control-label">Memory</label>
                    <div class="input-group">
                        <input type="text" name="memory" class="form-control" value="{{ old('memory') }}"/>
                        <span class="input-group-addon">MB</span>
                    </div>
                </div>
                <div class="form-group col-md-4 col-xs-4">
                    <label for="memory" class="control-label">Swap</label>
                    <div class="input-group">
                        <input type="text" name="swap" class="form-control" value="{{ old('swap', 0) }}"/>
                        <span class="input-group-addon">MB</span>
                    </div>
                </div>
                <div class="form-group col-md-4 col-xs-4">
                    <label for="memory" class="control-label">OOM Killer</label>
                    <div>
                        <span class="input-group-addon" style="height:36px;">
                            <input type="checkbox" name="oom_disabled"/>
                        </span>
                        <span class="input-group-addon" style="height:36px;">
                            Disable OOM Killer
                        </span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <p class="text-muted"><small>If you do not want to assign swap space to a server simply put <code>0</code> for the value. We suggest leaving OOM Killer enabled unless you know what you are doing, disabling it could cause your server to hang unexpectedly.</small><p>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-4 col-xs-4">
                    <label for="disk" class="control-label">Disk Space</label>
                    <div class="input-group">
                        <input type="text" name="disk" class="form-control" value="{{ old('disk') }}"/>
                        <span class="input-group-addon">MB</span>
                    </div>
                </div>
                <div class="form-group col-md-4 col-xs-4">
                    <label for="cpu" class="control-label">CPU Limit</label>
                    <div class="input-group">
                        <input type="text" name="cpu" class="form-control" value="{{ old('cpu', 0) }}"/>
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
                <div class="form-group col-md-4 col-xs-4">
                    <label for="io" class="control-label">Block I/O</label>
                    <div class="input-group">
                        <input type="text" name="io" class="form-control" value="{{ old('io', 500) }}"/>
                        <span class="input-group-addon">I/O</span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <p class="text-muted"><small>If you do not want to limit CPU usage set the value to <code>0</code>. To determine a value, take the number <em>physical</em> cores and multiply it by 100. For example, on a quad core system <code>(4 * 100 = 400)</code> there is <code>400%</code> available. To limit a server to using half of a single core, you would set the value to <code>50</code>. To allow a server to use up to two physical cores, set the value to <code>200</code>. BlockIO should be a value between <code>10</code> and <code>1000</code>. Please see <a href="https://docs.docker.com/reference/run/#block-io-bandwidth-blkio-constraint" target="_blank">this documentation</a> for more information about it.</small><p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6" id="load_services">
                <div class="well">
                    <div class="row">
                        <div class="ajax_loading_box" style="display:none;"><i class="fa fa-refresh fa-spin ajax_loading_position"></i></div>
                        <div class="form-group col-md-12">
                            <label for="service" class="control-label">Service Type</label>
                            <div>
                                <select name="service" id="getService" class="form-control">
                                    <option disabled selected> -- Select a Service</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-muted"><small>Select the type of service that this server will be running.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-12 hidden">
                            <label for="option" class="control-label">Service Option</label>
                            <div>
                                <select name="option" id="getOption" class="form-control">
                                    <option disabled selected> -- Select a Service Option</option>
                                </select>
                                <p class="text-muted"><small>Select the type of service that this server will be running.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="use_custom_image" class="control-label">Use Custom Docker Image</label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <input @if(old('use_custom_image') === 'use_custom_image')checked="checked"@endif type="checkbox" name="use_custom_image"/>
                                </span>
                                <input type="text" class="form-control" name="custom_image_name" value="{{ old('custom_image_name') }}" disabled />
                            </div>
                            <p class="text-muted"><small>If you would like to use a custom docker image for this server please enter it here. Most users can ignore this option.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="well" id="serviceOptions" style="display:none;">
            <div class="row">
                <div class="form-group col-md-12">
                    <h3 class="nopad">Service Setup &amp; Options</h3>
                    <hr />
                    <label for="startup" class="control-label">Startup Command</label>
                    <div class="input-group">
                        <span class="input-group-addon" id="startupExec"></span>
                        <input type="text" class="form-control" name="startup" value="{{ old('startup') }}" />
                    </div>
                    <p class="text-muted"><small>The following data replacers are avaliable for the startup command: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, and <code>@{{SERVER_PORT}}</code>. They will be replaced with the allocated memory, server ip, and server port respectively.</small></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">Some service options have additional environment variables that you can define for a given instance. They will show up below when you select a service option. If none show up, chances are that none were defined, and there is nothing to worry about.</div>
                </div>
            </div>
            <div class="row" id="serverVariables"></div>
        </div>
        <div class="well">
            <div class="row">
                <div class="col-md-12 text-center">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-primary btn-sm" value="Create New Server" />
                </div>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {

    $('#sidebar_links').find("a[href='/admin/servers/new']").addClass('active');

    $('input[name="use_custom_image"]').change(function () {
        $('input[name="custom_image_name"]').val('').prop('disabled', !($(this).is(':checked')));
    });

    var nodeData = null;
    var currentLocation = null;
    var currentNode = null;
    var currentService = null;
    $('#getLocation').on('change', function (event) {

        if ($('#getLocation').val() === '' || $('#getLocation').val() === currentLocation) {
            return;
        }

        currentLocation = $('#getLocation').val();
        currentNode = null;

        // Hide Existing, and Reset contents
        $('#getNode').html('<option disabled selected> -- Select a Node</option>').parent().parent().addClass('hidden');
        $('#getIP').html('<option disabled selected> -- Select an IP</option>').parent().parent().addClass('hidden');
        $('#getPort').html('').parent().parent().addClass('hidden');

        handleLoader('#load_settings', true);

        $.ajax({
            method: 'POST',
            url: '/admin/servers/new/get-nodes',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                location: $('#getLocation').val()
            }
        }).done(function (data) {
            //var data = $.parseJSON(data);
            $.each(data, function (i, item) {
                var isPublic = (item.public !== 1) ? '(Private Node)' : '';
                $('#getNode').append('<option value="' + item.id + '">' + item.name + ' ' + isPublic + '</option>');
            });
            $('#getNode').parent().parent().removeClass('hidden')
        }).fail(function (jqXHR) {
            alert('An error occured while attempting to load a list of nodes in this location.');
            currentLocation = null;
            console.error(jqXHR);
        }).always(function () {
            handleLoader('#load_settings');
        })
    });
    $('#getNode').on('change', function (event) {

        if ($('#getNode').val() === '' || $('#getNode').val() === currentNode) {
            return;
        }

        currentNode = $('#getNode').val();

        // Hide Existing, and Reset contents
        $('#getIP').html('<option disabled selected> -- Select an IP</option>').parent().parent().addClass('hidden');
        $('#getPort').html('').parent().parent().addClass('hidden');

        handleLoader('#load_settings', true);

        $.ajax({
            method: 'POST',
            url: '/admin/servers/new/get-ips',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                node: $('#getNode').val()
            }
        }).done(function (data) {
            nodeData = data;
            $.each(data, function (ip, ports) {
                $('#getIP').append('<option value="' + ip + '">' + ip + '</option>');
            });
            $('#getIP').parent().parent().removeClass('hidden');
        }).fail(function (jqXHR) {
            alert('An error occured while attempting to get IPs and Ports avaliable on this node.');
            currentNode = null;
            console.error(jqXHR);
        }).always(function () {
            handleLoader('#load_settings');
        });

    });

    $('#getIP').on('change', function (event) {

        if ($('#getIP').val() === '') {
            return;
        }

        $('#getPort').html('');

        $.each(nodeData[$('#getIP').val()], function (i, port) {
            $('#getPort').append('<option value="' + port +'">' + port + '</option>');
        });

        $('#getPort').parent().parent().removeClass('hidden');

    });

    $('#getService').on('change', function (event) {

        if ($('#getService').val() === '' || $('#getService').val() === currentService) {
            return;
        }

        currentService = $('#getService').val();
        handleLoader('#load_services', true);
        $('#serviceOptions').slideUp();
        $('#getOption').html('<option disabled selected> -- Select a Service Option</option>');

        $.ajax({
            method: 'POST',
            url: '/admin/servers/new/service-options',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                service: $('#getService').val()
            }
        }).done(function (data) {
            $('#startupExec').html(data.exec);
            $('input[name="startup"]').val(data.startup);
            $.each(data.options, function (i, option) {
                $('#getOption').append('<option value="' + option.id + '" data-image="' + option.docker_image + '">' + option.name + '</option>');
            });
            $('#getOption').parent().parent().removeClass('hidden');
        }).fail(function (jqXHR) {
            alert('An error occured while attempting to list options for this service.');
            currentService = null;
            console.error(jqXHR);
        }).always(function () {
            handleLoader('#load_services');
        });

    });

    $('#getOption').on('change', function (event) {

        handleLoader('#load_services', true);
        handleLoader('#serviceOptions', true);
        $('#serverVariables').html('');
        $('input[name="custom_image_name"]').val($(this).find(':selected').data('image'));

        $.ajax({
            method: 'POST',
            url: '/admin/servers/new/service-variables',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                option: $('#getOption').val()
            }
        }).done(function (data) {
            $.each(data, function (i, item) {
                var isRequired = (item.required === 1) ? '<code>Required</code>' : '';
                var dataAppend = ' \
                    <div class="form-group col-md-12">\
                        <label for="var_ref_' + item.id + '" class="control-label">' + item.name + '</label> ' + isRequired + '\
                        <div>\
                            <input type="text" autocomplete="off" name="env_' + item.env_variable + '" class="form-control" value="' + item.default_value + '" />\
                            <p class="text-muted"><small>' + item.description + '</small></p>\
                            <p class="text-muted"><small>Regex Requirements for Input: <code>' + item.regex + '</code></small></p>\
                            <p class="text-muted"><small>Access in Startup: <code>@{{' + item.env_variable + '}}</code></small></p>\
                        </div>\
                    </div>\
                ';
                $('#serverVariables').append(dataAppend);
            });
            $('#serviceOptions').slideDown();
        }).fail(function (jqXHR) {
            console.error(jqXHR);
        }).always(function () {
            handleLoader('#load_services');
            handleLoader('#serviceOptions');
        });

    });

    // Show Loading Animation
    function handleLoader (element, show) {

        var spinner = $(element).find('.ajax_loading_position');
        var popover = $(element).find('.ajax_loading_box');

        // Show Animation
        if (typeof show !== 'undefined') {
            var height = $(element).height();
            var width = $(element).width();
            var center_height = (height / 2) - 16;
            var center_width = (width / 2) - 16;
            spinner.css({
                'top': center_height,
                'left': center_width,
                'font-size': '32px'
            });
            popover.css({
                'height': height,
                'margin': '-20px 0 0 -5px',
                'width': width
            }).fadeIn();
        } else {
            popover.hide();
        }

    }

});
</script>
@endsection
