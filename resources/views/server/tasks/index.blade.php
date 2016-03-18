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
    Scheduled Tasks
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Manage Scheduled Tasks</h3><hr />
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Action</th>
                <th>Data</th>
                <th>Last Run</th>
                <th>Next Run</th>
                @can('delete-task', $server)<th></th>@endcan
                @can('toggle-task', $server)<th></th>@endcan
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                <tr @if($task->active === 0)class="text-disabled"@endif>
                    <td><a href="{{ route('server.tasks.view', [ $server->uuidShort, $task->id ]) }}">{{ $actions[$task->action] }}</a></td>
                    <td><code>{{ $task->data }}</code></td>
                    <td>{{ Carbon::parse($task->last_run)->toDayDateTimeString() }} <p class="text-muted"><small>({{ Carbon::parse($task->last_run)->diffForHumans() }})</small></p></td>
                    <td>
                        @if($task->active !== 0)
                            {{ Carbon::parse($task->next_run)->toDayDateTimeString() }} <p class="text-muted"><small>({{ Carbon::parse($task->next_run)->diffForHumans() }})</small></p>
                        @else
                            <em>n/a</em>
                        @endif
                    </td>
                    @can('delete-task', $server)
                        <td class="text-center text-v-center"><a href="#" data-action="delete-task" data-id="{{ $task->id }}"><i class="fa fa-fw fa-trash-o text-danger"></i></a></td>
                    @endcan
                    @can('toggle-task', $server)
                        <td class="text-center text-v-center"><a href="#" data-action="toggle-task" data-id="{{ $task->id }}"><i class="fa fa-fw fa-eye-slash text-primary"></i></a></td>
                    @endcan

                </tr>
            @endforeach
        </tbody>
    </table>
    @can('create-task', $server)
        <a href="{{ route('server.tasks.new', $server->uuidShort) }}"><button class="btn btn-sm btn-primary">Add Scheduled Task</button></a>
    @endcan
</div>
<script>
$(document).ready(function () {
    $('.server-tasks').addClass('active');
});
</script>
@endsection
