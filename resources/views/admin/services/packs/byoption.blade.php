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
    Service Packs for {{ $option->name }}
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li><a href="/admin/services">Services</a></li>
        <li><a href="{{ route('admin.services.packs') }}">Packs</a></li>
        <li><a href="{{ route('admin.services.packs.service', $service->id) }}">{{ $service->name }}</a></li>
        <li class="active">{{ $option->name }}</li>
    </ul>
    <h3 class="nopad">Service Packs</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Pack Name</th>
                <th>Version</th>
                <th>UUID</th>
                <th>Selectable</th>
                <th>Visible</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($packs as $pack)
                <tr>
                    <td><a href="{{ route('admin.services.packs.edit', $pack->id) }}">{{ $pack->name }}</a></td>
                    <td><code>{{ $pack->version }}</code></td>
                    <td><code>{{ $pack->uuid }}</code></td>
                    <td>@if($pack->selectable)<span class="label label-success"><i class="fa fa-check"></i></span>@else<span class="label label-default"><i class="fa fa-times"></i></span>@endif</td>
                    <td>@if($pack->visible)<span class="label label-success"><i class="fa fa-check"></i></span>@else<span class="label label-default"><i class="fa fa-times"></i></span>@endif</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5">
                    <a href="{{ route('admin.services.packs.new', $option->id) }}">
                        <button class="pull-right btn btn-xxs btn-primary"><i class="fa fa-plus"></i></button>
                    </a>
                    <a href="#upload" id="toggleUpload">
                        <button class="pull-right btn btn-xxs btn-default" style="margin-right:5px;"><i class="fa fa-upload"></i> Install from Template</button>
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/services/packs']").addClass('active');
    $('#toggleUpload').on('click', function (event) {
        event.preventDefault();
        var element = $(this);
        element.find('button').addClass('disabled');
        $.ajax({
            method: 'GET',
            url: '{{ route('admin.services.packs.uploadForm', $option->id) }}'
        }).fail(function (jqXhr) {
            console.error(jqXhr);
            alert('There was an error trying to create the upload form.');
        }).success(function (data) {
            $(data).modal();
        }).always(function () {
            element.find('button').removeClass('disabled');
        });
    });
});
</script>
@endsection
