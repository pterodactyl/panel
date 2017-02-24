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
            <div class="box">
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
                    <div class="form-group col-sm-4">
                        <label for="pLocationId">Location</label>
                        <select name="location_id" id="pLocationId" class="form-control">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->long }} ({{ $location->short }})</option>
                            @endforeach
                        </select>
                        <p class="small text-muted no-margin">The location in which this server will be deployed.</p>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pNodeId">Node</label>
                        <select name="node_id" id="pNodeId" class="form-control"></select>
                        <p class="small text-muted no-margin">The node which this server will be deployed to.</p>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pAllocation">Default Allocation</label>
                        <select name="allocation_id" id="pAllocation" class="form-control"></select>
                        <p class="small text-muted no-margin">The main allocation that will be assigned to this server.</p>
                    </div>
                    <div class="form-group col-sm-12">
                        <label for="pAllocationAdditional">Additional Allocation(s)</label>
                        <select name="allocation_additional" id="pAllocationAdditional" class="form-control" multiple></select>
                        <p class="small text-muted no-margin">Additional allocations to assign to this server on creation.</p>
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
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Resource Management</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-4">
                        <label for="pMemory">Memory</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="memory" id="pMemory" />
                            <span class="input-group-addon">MB</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pSwap">Swap</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="swap" id="pSwap" />
                            <span class="input-group-addon">MB</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pOOMDisabled">Out-of-Memory Killer</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <input type="checkbox" id="pOOMDisabled" name="oom_disabled"/>
                            </span>
                            <input type="text" class="form-control" readonly style="background:transparent !important;" value="Disable OOM Killer" />
                        </div>
                    </div>
                </div>
                <div class="box-footer no-border" style="padding: 0 10px 10px;">
                    <div class="callout callout-info callout-slim no-margin">
                        <p class="small no-margin">If you do not want to assign swap space to a server simply put <code>0</code> for the value, or <code>-1</code> to allow unlimited swap space. If you want to disable memory limiting on a server simply enter <code>0</code> into the memory field. We suggest leaving OOM Killer enabled unless you know what you are doing, disabling it could cause your server to hang unexpectedly.<p>
                    </div>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-4">
                        <label for="pDisk">Disk Space</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="disk" id="pDisk" />
                            <span class="input-group-addon">GB</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pCPU">CPU Limit</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="cpu" id="pCPU" />
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="pIO">Block IO Weight</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="500" name="io" id="pIO" />
                            <span class="input-group-addon">I/O</span>
                        </div>
                    </div>
                </div>
                <div class="box-footer no-border" style="padding: 0 10px 10px;">
                    <div class="callout callout-info callout-slim">
                        <p class="small no-margin">If you do not want to limit CPU usage set the value to <code>0</code>. To determine a value, take the number <em>physical</em> cores and multiply it by 100. For example, on a quad core system <code>(4 * 100 = 400)</code> there is <code>400%</code> available. To limit a server to using half of a single core, you would set the value to <code>50</code>. To allow a server to use up to two physical cores, set the value to <code>200</code>. BlockIO should be a value between <code>10</code> and <code>1000</code>. Please see <a href="https://docs.docker.com/engine/reference/run/#/block-io-bandwidth-blkio-constraint" target="_blank">this documentation</a> for more information about it.<p>
                    </div>
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
        $('#pLocationId').select2({
            placeholder: 'Select a Location',
        }).change();
        $('#pNodeId').select2({
            placeholder: 'Select a Node',
        });
        $('#pAllocation').select2({
            placeholder: 'Select a Default Allocation',
        });
        $('#pAllocationAdditional').select2({
            placeholder: 'Select Additional Allocations',
        });

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

    var lastActiveBox = null;
    $(document).on('click', function (event) {
        if (lastActiveBox !== null) {
            lastActiveBox.removeClass('box-primary');
        }

        lastActiveBox = $(event.target).closest('.box');
        lastActiveBox.addClass('box-primary');
    });

    var currentLocation = null;
    var curentNode = null;
    var NodeData = [];

    $('#pLocationId').on('change', function (event) {
        showLoader();
        currentLocation = $(this).val();
        currentNode = null;

        $.ajax({
            method: 'POST',
            url: Router.route('admin.servers.new.get-nodes'),
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            data: { location: currentLocation },
        }).done(function (data) {
            NodeData = data;
            $('#pNodeId').select2({data: data}).change();
        }).fail(function (jqXHR) {
            cosole.error(jqXHR);
            currentLocation = null;
        }).always(hideLoader);
    });

    $('#pNodeId').on('change', function (event) {
        currentNode = $(this).val();
        $.each(NodeData, function (i, v) {
            if (v.id == currentNode) {
                $('#pAllocation').select2({
                    data: v.allocations,
                    placeholder: 'Select a Default Allocation',
                });
                $('#pAllocationAdditional').select2({
                    data: v.allocations,
                    placeholder: 'Select Additional Allocations',
                })
            }
        });
    });
    </script>
@endsection
