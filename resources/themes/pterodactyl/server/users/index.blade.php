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
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th></th>
                            <th>@lang('strings.username')</th>
                            <th>@lang('strings.email')</th>
                            <th class="hidden-xs">@lang('strings.created_at')</th>
                            @can('view-subuser', $server)<th></th>@endcan
                            @can('delete-subuser', $server)<th></th>@endcan
                        </tr>
                        @foreach($subusers as $user)
                            <tr>
                                <td class="text-center middle"><img class="img-circle" src="https://www.gravatar.com/avatar/{{ md5($user->email) }}?s=128" style="height:20px;" alt="User Image"></td>
                                <td class="middle">{{ $user->username }}
                                <td class="middle"><code>{{ $user->email }}</code></td>
                                <td class="middle hidden-xs">{{ $user->created_at }}</td>
                                @can('view-subuser', $server)
                                    <td class="text-center middle">
                                        <a href="{{ route('server.subusers.view', ['server' => $server->uuidShort, 'id' => md5($user->id)]) }}">
                                            <button class="btn btn-xs btn-primary">@lang('server.users.configure')</button>
                                        </a>
                                    </td>
                                @endcan
                                @can('delete-subuser', $server)
                                    <td class="text-center middle">
                                        <a href="#/delete/{{ md5($user->id) }}" data-action="delete" data-id="{{ md5($user->id) }}">
                                            <button class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i></button>
                                        </a>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('create-subuser', $server)
                <div class="box-footer with-border">
                    <a href="{{ route('server.subusers.new', $server->uuidShort) }}"><button class="btn btn-sm btn-success pull-right">@lang('server.users.add')</button></a>
                </div>
            @endcan
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
@endsection
