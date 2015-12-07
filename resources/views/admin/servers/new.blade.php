@extends('layouts.admin')

@section('title')
    Server List
@endsection

@section('content')
<div class="col-md-9">
    <ul class="breadcrumb">
		<li><a href="/admin">Admin Control</a></li>
		<li><a href="/admin/servers">Servers</a></li>
        <li class="active">Create New Server</li>
	</ul>
    <h3>Create New Server</h3><hr />
    <form action="#" method="POST">
        <div class="well">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="server_name" class="control-label">Server Name</label>
                    <div>
                        <input type="text" autocomplete="off" name="server_name" class="form-control" />
                        <p class="text-muted" style="margin: 0 0 -10.5px;"><small><em>Character limits: <code>a-zA-Z0-9_-</code> and <code>[Space]</code> (max 35 characters)</em></small></p>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="server_name" class="control-label">Owner Email</label>
                    <div>
                        <input type="text" autocomplete="off" name="owner_email" class="form-control" />
                    </div>
                </div>
            </div>
        </div>
        <div id="load_settings">
            <div class="well">
                <div class="row">
                    <div class="ajax_loading_box" style="display:none;"><i class="fa fa-refresh fa-spin" id="position_me"></i></div>
                    <div class="form-group col-md-6">
                        <label for="location" class="control-label">Server Location</label>
                        <div>
                            <select name="location" id="getLocation" class="form-control">
                                <option></option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->long }} ({{ $location->short }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-6 hidden">
                        <label for="location" class="control-label">Server Node</label>
                        <div>
                            <select name="node" id="getNode" class="form-control">
                                <option></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6 hidden">
                        <label for="location" class="control-label">Server IP</label>
                        <div>
                            <select name="node" id="getIP" class="form-control">
                                <option></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-6 hidden">
                        <label for="location" class="control-label">Server Port</label>
                        <div>
                            <select name="node" id="getPort" class="form-control"></select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    var nodeData = null;
    var currentLocation = null;
    var currentNode = null;
    $('#getLocation').on('change', function (event) {

        if ($('#getLocation').val() === '' || $('#getLocation').val() === currentLocation) {
            return;
        }

        currentLocation = $('#getLocation').val();
        currentNode = null;

        // Hide Existing, and Reset contents
        $('#getNode').html('<option></option>').parent().parent().addClass('hidden');
        $('#getIP').html('<option></option>').parent().parent().addClass('hidden');
        $('#getPort').html('').parent().parent().addClass('hidden');

        handleLoader(true);

        $.ajax({
            method: 'POST',
            url: '/admin/ajax/new/server/get-nodes',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                location: $('#getLocation').val()
            }
        }).done(function (data) {
            var data = $.parseJSON(data);
            $.each(data, function (i, item) {
                var isPublic = (item.public !== 1) ? '(Private Node)' : '';
                $('#getNode').append('<option value="' + item.id + '">' + item.name + ' ' + isPublic + '</option>');
            });
            $('#getNode').parent().parent().removeClass('hidden')
        }).fail(function (jqXHR) {
            alert('An error occured while attempting to load a list of nodes in this location.');
            console.log(jqXHR);
        }).always(function () {
            handleLoader();
        })
    });
    $('#getNode').on('change', function (event) {

        if ($('#getNode').val() === '' || $('#getNode').val() === currentNode) {
            return;
        }

        currentNode = $('#getNode').val();

        // Hide Existing, and Reset contents
        $('#getIP').html('<option></option>').parent().parent().addClass('hidden');
        $('#getPort').html('').parent().parent().addClass('hidden');

        handleLoader(true);

        $.ajax({
            method: 'POST',
            url: '/admin/ajax/new/server/get-ips',
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
            console.log(jqXHR);
        }).always(function () {
            handleLoader();
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

    // Show Loading Animation
    function handleLoader (show) {

        // Show Animation
        if (show === true){
            var height = $('#load_settings').height();
            var width = $('#load_settings').width();
            var center_height = (height / 2) - 16;
            var center_width = (width / 2) - 16;
            $('#position_me').css({
                'top': center_height,
                'left': center_width,
                'font-size': '32px'
            });
            $(".ajax_loading_box").css({
                'height': height,
                'margin': '-20px 0 0 -5px',
                'width': width
            }).fadeIn();
        } else {
            $('.ajax_loading_box').fadeOut(100);
        }

    }

});
</script>
@endsection
