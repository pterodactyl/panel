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

@section('title')
    Viewing Subusers
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Manage Sub-Users</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Email</th>
                <th>Created</th>
                @can('view-subuser', $server)<th></th>@endcan
                @can('delete-subuser', $server)<th></th>@endcan
            </tr>
        </thead>
        <tbody>
            @foreach($subusers as $user)
                <tr>
                    <td><code>{{ $user->a_userEmail }}</code></td>
                    <td>{{ $user->created_at }}</td>
                    @can('view-subuser', $server)
                        <td class="text-center"><a href="{{ route('server.subusers.view', ['server' => $server->uuidShort, 'id' => md5($user->id)]) }}" class="text-success"><i class="fa fa-wrench"></i></a></td>
                    @endcan
                    @can('delete-subuser', $server)
                        <td class="text-center"><a href="#/delete/{{ md5($user->id) }}" data-action="delete" data-id="{{ md5($user->id) }}" class="text-danger"><i class="fa fa-trash-o"></i></a></td>
                    @endcan
                </tr>
            @endforeach
        </tbody>
    </table>
    @can('create-subuser', $server)
        <div class="well">
            <div class="row">
                <div class="col-md-12">
                    <a href="{{ route('server.subusers.new', $server->uuidShort) }}"><button class="btn btn-sm btn-success">Add New Subuser</button></a>
                </div>
            </div>
        </div>
    @endcan
</div>
<script>
$(document).ready(function () {
    $('.server-users').addClass('active');
    $('[data-action="delete"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            type: 'warning',
            title: 'Delete Subuser',
            text: 'This will immediately remove this user from this server and revoke all permissions.',
            showCancelButton: true,
            showConfirmButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('server.subusers', $server->uuidShort) }}/delete/' + self.data('id'),
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}'
                }
            }).done(function () {
                self.parent().parent().slideUp();
                swal({
                    type: 'success',
                    title: '',
                    text: 'Subuser was successfully deleted.'
                });
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                var error = 'An error occured while trying to process this request.';
                if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                    error = jqXHR.responseJSON.error;
                }
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: error
                });
            });
        });
    });
});
</script>
@endsection
