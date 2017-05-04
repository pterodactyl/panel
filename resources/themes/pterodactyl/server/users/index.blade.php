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
@extends('layouts.master')

@section('title')
    @lang('server.users.header')
@endsection

@section('content-header')
    <h1>@lang('server.users.header')<small>@lang('server.users.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li class="active">@lang('navigation.server.subusers')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.users.list')</h3>
                @can('create-subuser', $server)
                    <div class="box-tools">
                        <a href="{{ route('server.subusers.new', $server->uuidShort) }}"><button class="btn btn-primary btn-sm">Create New</button></a>
                    </div>
                @endcan
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th></th>
                            <th>@lang('strings.username')</th>
                            <th>@lang('strings.email')</th>
                            <th class="text-center">@lang('strings.2fa')</th>
                            <th class="hidden-xs">@lang('strings.created_at')</th>
                            @can('view-subuser', $server)<th></th>@endcan
                            @can('delete-subuser', $server)<th></th>@endcan
                        </tr>
                        @foreach($subusers as $subuser)
                            <tr>
                                <td class="text-center middle"><img class="img-circle" src="https://www.gravatar.com/avatar/{{ md5($subuser->user->email) }}?s=128" style="height:20px;" alt="User Image"></td>
                                <td class="middle">{{ $subuser->user->username }}
                                <td class="middle"><code>{{ $subuser->user->email }}</code></td>
                                <td class="middle text-center">
                                    @if($subuser->user->use_totp)
                                        <i class="fa fa-lock text-green"></i>
                                    @else
                                        <i class="fa fa-unlock text-red"></i>
                                    @endif
                                </td>
                                <td class="middle hidden-xs">{{ $subuser->user->created_at }}</td>
                                @can('view-subuser', $server)
                                    <td class="text-center middle">
                                        <a href="{{ route('server.subusers.view', ['server' => $server->uuidShort, 'id' => $subuser->id]) }}">
                                            <button class="btn btn-xs btn-primary">@lang('server.users.configure')</button>
                                        </a>
                                    </td>
                                @endcan
                                @can('delete-subuser', $server)
                                    <td class="text-center middle">
                                        <a href="#/delete/{{ $subuser->id }}" data-action="delete" data-id="{{ $subuser->id }}">
                                            <button class="btn btn-xs btn-danger">@lang('strings.revoke')</button>
                                        </a>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script>
    $(document).ready(function () {
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
                    url: Router.route('server.subusers.delete', {
                        server: Pterodactyl.server.uuidShort,
                        id: self.data('id'),
                    }),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
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
