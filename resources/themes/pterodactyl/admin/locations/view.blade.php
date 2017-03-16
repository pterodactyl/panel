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
    Locations &rarr; View &rarr; {{ $location->short }}
@endsection

@section('content-header')
    <h1>{{ $location->short }}<small>{{ str_limit($location->long, 75) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.locations') }}">Locations</a></li>
        <li class="active">{{ $location->short }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Location Details</h3>
            </div>
            <form action="{{ route('admin.locations.view', $location->id) }}" method="POST">
                <div class="box-body">
                    <div class="form-group">
                        <label for="pShort" class="form-label">Short Code</label>
                        <input type="text" id="pShort" name="short" class="form-control" value="{{ $location->short }}" />
                    </div>
                    <div class="form-group">
                        <label for="pLong" class="form-label">Description</label>
                        <textarea id="pLong" name="long" class="form-control" rows="4">{{ $location->long }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button name="action" value="delete" class="btn btn-sm btn-danger pull-left muted muted-hover"><i class="fa fa-trash-o"></i></button>
                    <button name="action" value="edit" class="btn btn-sm btn-primary pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Nodes</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>FQDN</th>
                        <th>Servers</th>
                    </tr>
                    @foreach($location->nodes as $node)
                        <tr>
                            <td><code>{{ $node->id }}</code></td>
                            <td><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td>
                            <td><code>{{ $node->fqdn }}</code></td>
                            <td>{{ $node->servers->count() }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
