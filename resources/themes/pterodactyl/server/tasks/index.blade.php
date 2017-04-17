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
    @lang('server.tasks.header')
@endsection

@section('content-header')
    <h1>@lang('server.tasks.header')<small>@lang('server.tasks.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li class="active">@lang('navigation.server.task_management')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('server.tasks.current')</h3>
                <div class="box-tools">
                    <a href="{{ route('server.tasks.new', $server->uuidShort) }}"><button class="btn btn-primary btn-sm">Create New</button></a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>@lang('strings.action')</th>
                            <th>@lang('strings.data')</th>
                            <th>@lang('strings.queued')</th>
                            <th>@lang('strings.last_run')</th>
                            <th>@lang('strings.next_run')</th>
                            <th></th>
                            <th></th>
                        </tr>
                        @foreach($tasks as $task)
                            <tr @if(! $task->active)class="muted muted-hover"@endif>
                                <td class="middle">{{ $actions[$task->action] }}</td>
                                <td class="middle"><code>{{ $task->data }}</code></td>
                                <td class="middle">
                                    @if ($task->queued)
                                        <span class="label label-success">@lang('strings.yes')</span>
                                    @else
                                        <span class="label label-default">@lang('strings.no')</span>
                                    @endif
                                </td>
                                <td class="middle">
                                @if($task->last_run)
                                    {{ Carbon::parse($task->last_run)->toDayDateTimeString() }}<br /><span class="text-muted small">({{ Carbon::parse($task->last_run)->diffForHumans() }})</span>
                                @else 
                                    @lang('strings.not_run_yet')
                                @endif
                                </td>
                                <td class="middle">
                                    @if($task->active !== 0)
                                        {{ Carbon::parse($task->next_run)->toDayDateTimeString() }}<br /><span class="text-muted small">({{ Carbon::parse($task->next_run)->diffForHumans() }})</span>
                                    @else
                                        <em>n/a</em>
                                    @endif
                                </td>
                                @can('delete-task', $server)
                                    <td class="text-center middle"><a href="#" data-action="delete-task" data-id="{{ $task->id }}"><i class="fa fa-fw fa-trash-o text-danger" data-toggle="tooltip" data-placement="top" title="@lang('strings.delete')"></i></a></td>
                                @endcan
                                @can('toggle-task', $server)
                                    <td class="text-center middle"><a href="#" data-action="toggle-task" data-active="{{ $task->active }}" data-id="{{ $task->id }}"><i class="fa fa-fw fa-eye-slash text-primary" data-toggle="tooltip" data-placement="top" title="@lang('server.tasks.toggle')"></i></a></td>
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
    {!! Theme::js('js/frontend/tasks.js') !!}
@endsection
