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
    Database Management
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">Databases</li>
    </ul>
    <h3>Manage Databases</h3><hr />
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        <li class="active"><a href="#tab_databases" data-toggle="tab">Databases</a></li>
        <li><a href="#tab_dbservers" data-toggle="tab">Database Servers</a></li>
        <li><a href="{{ route('admin.databases.new') }}"><i class="fa fa-plus"></i></a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane {{ Request::input('tab') == 'tab_dbservers' ? '' : 'active' }}" id="tab_databases">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <table class="table table-bordered table-hover" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Server</th>
                                <th>Database</th>
                                <th>Username</th>
                                <th>Connection</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($databases as $db)
                                <tr>
                                    <td>{{ $db->a_serverName }}</td>
                                    <td>{{ $db->database }}</td>
                                    <td>{{ $db->username }} ({{ $db->remote }})</td>
                                    <td><code>{{ $db->a_host }}:{{ $db->a_port }}</code></td>
                                    <td class="text-center"><a href="/admin/servers/view/{{ $db->a_serverId }}?tab=tab_database"><i class="fa fa-search"></i></a></td>
                                    <td class="text-center"><a href="#" data-action="delete" data-type="delete" data-attr="{{ $db->id }}" class="text-danger"><i class="fa fa-trash-o"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="col-md-12 text-center">
                        {{ $databases->appends('tab', 'tab_databases')->render() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane {{ Request::input('tab') == 'tab_dbservers' ? 'active' : '' }}" id="tab_dbservers">
            <div class="panel panel-default">
                <div class="panel-heading"></div>
                <div class="panel-body">
                    <table class="table table-bordered table-hover" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Connection</th>
                                <th>Username</th>
                                <th class="text-center">Databases</th>
                                <th>Linked Node</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dbh as $db)
                                <tr>
                                    <td>{{ $db->name }}</td>
                                    <td><code>{{ $db->host }}:{{ $db->port }}</code></td>
                                    <td>{{ $db->username }}</td>
                                    <td class="text-center">{{ $db->c_databases }}</td>
                                    <td>@if(is_null($db->a_linkedNode))<em>unlinked</em>@else{{ $db->a_linkedNode }}@endif</td>
                                    <td class="text-center"><a href="#" class="text-danger" data-action="delete" data-type="delete-server" data-attr="{{ $db->id }}"><i class="fa fa-trash-o"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="col-md-12 text-center">
                        {{ $dbh->appends('tab', 'tab_dbservers')->render() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/databases']").addClass('active');
    $('[data-action="delete"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            title: '',
            type: 'warning',
            text: 'Are you sure that you want to remove this database from the system?',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('admin.databases') }}/' + self.data('type') + '/' + self.data('attr'),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function () {
                self.parent().parent().slideUp();
                swal({
                    title: '',
                    type: 'success',
                    text: ''
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
});
</script>
@endsection
