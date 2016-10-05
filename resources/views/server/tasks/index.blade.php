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
                    {{-- <td><a href="{{ route('server.tasks.view', [ $server->uuidShort, $task->id ]) }}">{{ $actions[$task->action] }}</a></td> --}}
                    <td>{{ $actions[$task->action] }}</td>
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
                        <td class="text-center align-middle"><a href="#" data-action="delete-task" data-id="{{ $task->id }}"><i class="fa fa-fw fa-trash-o text-danger" data-toggle="tooltip" data-placement="top" title="Delete"></i></a></td>
                    @endcan
                    @can('toggle-task', $server)
                        <td class="text-center align-middle"><a href="#" data-action="toggle-task" data-active="{{ $task->active }}" data-id="{{ $task->id }}"><i class="fa fa-fw fa-eye-slash text-primary" data-toggle="tooltip" data-placement="top" title="Toggle Status"></i></a></td>
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
    $('[data-toggle="tooltip"]').tooltip();

    @can('delete-task', $server)
        $('[data-action="delete-task"]').click(function (event) {
            var self = $(this);
            swal({
                type: 'error',
                title: 'Delete Task?',
                text: 'Are you sure you want to delete this task? There is no undo.',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: false,
                confirmButtonText: 'Delete Task',
                confirmButtonColor: '#d9534f',
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('server.tasks', $server->uuidShort) }}/delete/' + self.data('id'),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).done(function (data) {
                    swal({
                        type: 'success',
                        title: '',
                        text: 'Task has been deleted.'
                    });
                    self.parent().parent().slideUp();
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        text: 'An error occured while attempting to delete this task.'
                    });
                });
            });
        });
    @endcan
    @can('toggle-task', $server)
        $('[data-action="toggle-task"]').click(function (event) {
            var self = $(this);
            swal({
                type: 'info',
                title: 'Toggle Task',
                text: 'This will toggle the selected task.',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: false,
                confirmButtonText: 'Continue',
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'POST',
                    url: '{{ route('server.tasks', $server->uuidShort) }}/toggle/' + self.data('id'),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).done(function (data) {
                    swal({
                        type: 'success',
                        title: '',
                        text: 'Task has been toggled.'
                    });
                    if (data.status !== 1) {
                        self.parent().parent().addClass('text-disabled');
                    } else {
                        self.parent().parent().removeClass('text-disabled');
                    }
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        text: 'An error occured while attempting to toggle this task.'
                    });
                });
            });
        });
    @endcan

});
</script>
@endsection
