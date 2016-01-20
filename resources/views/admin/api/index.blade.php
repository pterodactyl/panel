{{--
    Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
--}}
@extends('layouts.admin')

@section('title')
    API Management
@endsection

@section('content')
<div class="col-md-12">
    <ul class="breadcrumb">
        <li><a href="/admin">Admin Control</a></li>
        <li class="active">API Management</li>
    </ul>
    <h3>API Key Information</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>API Public Key</th>
                <th>Allowed IPs</th>
                <th>Permissions</th>
                <th class="text-center">Created</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($keys as $key)
                <tr>
                    <td><code>{{ $key->public }}</code></td>
                    <td>
                        @if (is_null($key->allowed_ips))
                            <code>*</code>
                        @else
                            @foreach(json_decode($key->allowed_ips) as $ip)
                                <code style="line-height:2;">{{ $ip }}</code><br />
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @foreach(json_decode($key->permissions) as &$perm)
                            <code style="line-height:2;">{{ $perm->permission }}</code><br />
                        @endforeach
                    </td>
                    <td class="text-center">{{ $key->created_at }}</td>
                    <td class="text-center"><a href="#delete" class="text-danger" data-action="delete" data-attr="{{ $key->public }}"><i class="fa fa-trash"></i></a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="well">
        <a href="{{ route('admin.api.new') }}"><button class="btn btn-success btn-sm">Create New API Key</button></a>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#sidebar_links').find("a[href='/admin/api']").addClass('active');
    $('[data-action="delete"]').click(function (event) {
        var self = $(this);
        event.preventDefault();
        swal({
            type: 'error',
            title: 'Revoke API Key',
            text: 'Once this API key is revoked any applications currently using it will stop working.',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Revoke',
            confirmButtonColor: '#d9534f',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('admin.api.revoke') }}/' + self.data('attr'),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).done(function (data) {
                swal({
                    type: 'success',
                    title: '',
                    text: 'API Key has been revoked.'
                });
                self.parent().parent().slideUp();
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: 'An error occured while attempting to revoke this key.'
                });
            });
        });
    });
});
</script>
@endsection
