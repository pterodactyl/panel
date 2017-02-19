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
    New Server
@endsection

@section('content-header')
    <h1>Create Server<small>Add a new server to the panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.servers') }}">Servers</a></li>
        <li class="active">Create Server</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.servers.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Core Details</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-6">
                        <label for="pName">Server Name</label>
                        <input type="text" class="form-control" id="pName" name="name" placeholder="Server Name">
                        <p class="small text-muted no-margin">Character limits: <code>a-z A-Z 0-9 _ - .</code> and <code>[Space]</code> (max 200 characters).</p>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="pUserId">Server Owner</label>
                        <select class="form-control" style="padding-left:0;" name="user_id" id="pUserId"></select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="overlay" id="allocationLoader" style="display:none;"><i class="fa fa-refresh fa-spin"></i></div>
                <div class="box-header with-border">
                    <h3 class="box-title">Allocation Management</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-6">
                        <label for="pLocationId">Location</label>
                        <select name="location_id" id="pLocationId" class="form-control">
                            <option disabled selected>Select a Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->long }} ({{ $location->short }})</option>
                            @endforeach
                        </select>
                        <p class="small text-muted no-margin">The location in which this server will be deployed.</p>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="pNodeId">Node</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            <option disabled selected>Select a Node</option>
                        </select>
                        <p class="small text-muted no-margin">The node which this server will be deployed to.</p>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="pIp">IP Address</label>
                        <select name="ip" id="pIp" class="form-control">
                            <option disabled selected>Select an IP</option>
                        </select>
                        <p class="small text-muted no-margin">The IP address that this server will be allocated to.</p>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="pPort">Port</label>
                        <select name="port" id="pPort" class="form-control">
                            <option disabled selected>Select a Port</option>
                        </select>
                        <p class="small text-muted no-margin">The port that this server will be allocated to.</p>
                    </div>
                </div>
                <div class="box-footer">
                    <p class="text-muted small no-margin">
                        <input type="checkbox" name="auto_deploy" id="pAutoDeploy" />
                        <label for="pAutoDeploy">Check this box if you want the panel to automatically select a node and allocation for this server in the given location.</label>
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $(document).ready(function() {
        $('#pLocationId').select2();
        $('#pNodeId').select2();
        $('#pIp').select2();
        $('#pPort').select2();

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
    });

    function hideLoader() {
        $('#allocationLoader').hide();
    }

    function showLoader() {
        $('#allocationLoader').show();
    }

    var currentLocation = null;
    var curentNode = null;
    var currentIP = null;

    var NodeDataIdentifier = null;
    var NodeData = [];
    var AllocationsForNode = null;

    $('#pLocationId').on('change', function (event) {
        showLoader()
        currentLocation = $(this).val();
        currentNode = null;

        $.ajax({
            method: 'POST',
            url: Router.route('admin.servers.new.get-nodes'),
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            data: { location: currentLocation },
        }).done(function (data) {
            NodeData = data;
            console.log(data);
            $('#pNodeId').select2({data: data});
        }).fail(function (jqXHR) {
            cosole.error(jqXHR);
            currentLocation = null;
        }).always(hideLoader);
    });

    $('#pNodeId').on('change', function (event) {
        currentNode = $(this).val();
        $.each(NodeData, function (i, v) {
            if (v.id == currentNode) {
                NodeDataIdentifier = i;
                $('#pIp').select2({
                    data: $.map(v.allocations, function (item) {
                        return {
                            id: item.ip,
                            text: item.ip,
                        }
                    }),
                })
            }
        });
    });

    $('#pIp').on('change', function (event) {
        currentIP = $(this).val();
        $.each(NodeData[NodeDataIdentifier].allocations, function (i, v) {
            if (v.ip == currentIP) {
                $('#pPort').val(null);
                $('#pPort').select2({
                    data: $.map(v.ports, function (item) {
                        return {
                            id: item,
                            text: item,
                        }
                    }),
                })
            }
        });
    });
    </script>
@endsection
