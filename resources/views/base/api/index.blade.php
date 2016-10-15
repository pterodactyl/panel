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
@extends('layouts.master')

@section('title', 'API Access')

@section('sidebar-server')
@endsection

@section('content')
<div class="col-md-12">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Public Key</th>
                <th>Memo</th>
                <th class="text-center">Created</th>
                <th class="text-center">Expires</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($keys as $key)
                <tr class="align-middle">
                    <td><code>{{ $key->public }}</code></td>
                    <td>{{ $key->memo }}</td>
                    <td class="text-center">{{ (new Carbon($key->created_at))->toDayDateTimeString() }}</td>
                    <td class="text-center">
                        @if(is_null($key->expires_at))
                            <span class="label label-default">Never</span>
                        @else
                            {{ (new Carbon($key->expires_at))->toDayDateTimeString() }}
                        @endif
                    </td>
                    <td class="text-center"><a href="#delete" class="text-danger" data-action="delete" data-attr="{{ $key->public }}"><i class="fa fa-trash"></i></a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="well">
        <a href="{{ route('account.api.new') }}"><button class="btn btn-success btn-sm">Create New API Key</button></a>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find('a[href="/account/api"]').addClass('active');
});
</script>
@endsection
