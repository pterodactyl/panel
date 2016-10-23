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
    Server List
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">Servers</li>
    </ul>
    <h3>All Servers</h3><hr />
    <form method="GET" style="margin-bottom:20px;">
        <div class="input-group">
            <input type="text" name="filter" class="form-control" value="{{ urldecode(Input::get('filter')) }}" placeholder="search term" />
            <div class="input-group-btn">
                <button type="submit" class="btn btn-sm btn-primary">Filter Servers</button>
            </div>
        </div>
    </form>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Server Name</th>
                <th>Owner</th>
                <th>Node</th>
                <th class="hidden-xs">SFTP Username</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($servers as $server)
                <tr @if($server->suspended === 1)class="warning"@endif data-server="{{ $server->uuidShort }}">
                    <td><a href="/admin/servers/view/{{ $server->id }}">{{ $server->name }}</a>@if($server->suspended === 1) <span class="label label-warning">Suspended</span>@endif</td>
                    <td><a href="/admin/users/view/{{ $server->owner }}">{{ $server->a_ownerEmail }}</a></td>
                    <td><a href="/admin/nodes/view/{{ $server->node }}">{{ $server->a_nodeName }}</a></td>
                    <td class="hidden-xs"><code>{{ $server->username }}</code></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $servers->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/servers']").addClass('active');
});
</script>
@endsection
