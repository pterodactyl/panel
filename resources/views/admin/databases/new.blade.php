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
    Add New Database Server
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/databases">Databases</a></li>
        <li class="active">New Database Server</li>
    </ul>
    <h3>New Database Server</h3><hr />
    <div class="alert alert-info">If you are trying to add a new database to an existing server please visit that server's control page and visit the 'Databases' tab. This page is for adding a new database server that individual per-server databases can be deployed to.</div>
    <form action="{{ route('admin.databases.new') }}" method="POST">
        <div class="row">
            <div class="form-group col-xs-6">
                <label class="control-label">Descriptive Name:</label>
                <div>
                    <input type="text" class="form-control" name="name" value="{{ old('name') }}" />
                    <p class="text-muted"><small>Enter a descriptive name for this database server.</small></p>
                </div>
            </div>
            <div class="form-group col-xs-6">
                <label class="control-label">Linked Node:</label>
                <div>
                    <select name="linked_node" class="form-control">
                        <option>None</option>
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" @if((int) old('linked_node') === $node->id) selected="selected" @endif>{{ $node->name }} ({{ $node->location->short }})</option>
                        @endforeach
                    </select>
                    <p class="text-muted"><small>A linked node implies that this Database Server is running on that node and it will be auto-selected when adding a database to servers on that node.</small></p>
                </div>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="form-group col-xs-6">
                <label class="control-label">Database Host:</label>
                <div>
                    <input type="text" class="form-control" name="host" value="{{ old('host') }}" />
                    <p class="text-muted"><small>Enter the IP address that this database server is listening on.</small></p>
                </div>
            </div>
            <div class="form-group col-xs-6">
                <label class="control-label">Database Port:</label>
                <div>
                    <input type="text" class="form-control" name="port" value="{{ old('port', 3306) }}" />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label class="control-label">Database Username:</label>
                <div>
                    <input type="text" class="form-control" name="username" value="{{ old('username') }}" />
                    <p class="text-muted"><small>The panel must be able to access this database, you may need to allow access from <code>{{ Request::server('SERVER_ADDR') }}</code> for this user.</small></p>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label class="control-label">Database User Passwod:</label>
                <div>
                    <input type="password" class="form-control" autocomplete="off" name="password" />
                </div>
            </div>
        </div>
        <div class="well well-sm">
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" value="Add Database Server" class="btn btn-sm btn-primary" />
                </div>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/databases']").addClass('active');
});
</script>
@endsection
