{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}
{{-- Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com> --}}

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
    Account List
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">Accounts</li>
    </ul>
    <h3>All Registered Users</h3><hr />
    <form method="GET" style="margin-bottom:20px;">
        <div class="input-group">
            <input type="text" name="filter" class="form-control" value="{{ urldecode(Input::get('filter')) }}" placeholder="search term" />
            <div class="input-group-btn">
                <button type="submit" class="btn btn-sm btn-primary">Filter Users</button>
            </div>
        </div>
    </form>
    <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</td>
                <th>Email</td>
                <th>Client Name</th>
                <th>Username</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="align-middle">
                    <td><code>#{{ $user->id }}</code></td>
                    <td><a href="{{ route('admin.users.view', $user->id) }}">{{ $user->email }}</a></td>
                    <td>{{ $user->name_last }}, {{ $user->name_first }}</td>
                    <td><code>{{ $user->username }}</code></td>
                    <td class="text-center"><img src="https://www.gravatar.com/avatar/{{ md5(strtolower($user->email)) }}?s=20" class="img-circle" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-md-12 text-center">{!! $users->render() !!}</div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/users']").addClass('active');
});
</script>
@endsection
