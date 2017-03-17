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
    @lang('server.users.new.header')
@endsection

@section('content-header')
    <h1>@lang('server.users.edit.header')<small>@lang('server.users.edit.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li><a href="{{ route('server.subusers', $server->uuidShort) }}">@lang('navigation.server.subusers')</a></li>
        <li class="active">@lang('server.users.update')</li>
    </ol>
@endsection

@section('content')
@can('edit-subuser', $server)
<form action="{{ route('server.subusers.view', [ 'uuid' => $server->uuidShort, 'id' => $subuser->id ]) }}" method="POST">
@endcan
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="form-group">
                        <label class="control-label">@lang('server.users.new.email')</label>
                        <div>
                            {!! csrf_field() !!}
                            <input type="email" class="form-control" disabled value="{{ $subuser->user->email }}" />
                        </div>
                    </div>
                </div>
                @can('edit-subuser', $server)
                    <div class="box-body">
                        <div class="btn-group pull-left">
                            <a id="selectAllCheckboxes" class="btn btn-sm btn-default">@lang('strings.select_all')</a>
                            <a id="unselectAllCheckboxes" class="btn btn-sm btn-default">@lang('strings.select_none')</a>
                        </div>
                        <input type="submit" name="submit" value="@lang('server.users.update')" class="pull-right btn btn-sm btn-primary" />
                    </div>
                @endcan
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            {{-- Left Side --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.power_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                <input name="permissions[]" type="checkbox" @if(isset($permissions['power-start']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-start" />
                                <label class="form-label">@lang('server.users.new.start.title')</label>
                                <p class="text-muted small">@lang('server.users.new.start.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['power-stop']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-stop" />
                                    <strong>@lang('server.users.new.stop.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.stop.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['power-restart']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-restart" />
                                    <strong>@lang('server.users.new.restart.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.restart.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['power-kill']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-kill" />
                                    <strong>@lang('server.users.new.kill.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.kill.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['send-command']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="send-command" />
                                    <strong>@lang('server.users.new.command.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.command.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.subuser_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['list-subusers']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="list-subusers" />
                                    <strong>@lang('server.users.new.list_subusers.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.list_subusers.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['view-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-subuser" />
                                    <strong>@lang('server.users.new.view_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_subuser.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['edit-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="edit-subuser" />
                                    <strong>@lang('server.users.new.edit_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.edit_subuser.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['create-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="create-subuser" />
                                    <strong>@lang('server.users.new.create_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.create_subuser.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['delete-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="delete-subuser" />
                                    <strong>@lang('server.users.new.delete_subuser.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.delete_subuser.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.server_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['set-connection']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="set-connection" />
                                    <strong>@lang('server.users.new.set_connection.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.set_connection.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['view-startup']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-startup" />
                                    <strong>@lang('server.users.new.view_startup.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_startup.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['edit-startup']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="edit-startup" />
                                    <strong>@lang('server.users.new.edit_startup.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.edit_startup.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.sftp_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['view-sftp']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-sftp" />
                                    <strong>@lang('server.users.new.view_sftp.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_sftp.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['view-sftp-password']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-sftp-password" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.view_sftp_password.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_sftp_password.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['reset-sftp']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="reset-sftp" />
                                    <strong>@lang('server.users.new.reset_sftp.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.reset_sftp.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            {{-- Right Side --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.file_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['list-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="list-files" />
                                    <strong>@lang('server.users.new.list_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.list_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['edit-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="edit-files" />
                                    <strong>@lang('server.users.new.edit_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.edit_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['save-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="save-files" />
                                    <strong>@lang('server.users.new.save_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.save_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['move-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="move-files" />
                                    <strong>@lang('server.users.new.move_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.move_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['copy-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="copy-files" />
                                    <strong>@lang('server.users.new.copy_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.copy_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['compress-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="compress-files" />
                                    <strong>@lang('server.users.new.compress_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.compress_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['decompress-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="decompress-files" />
                                    <strong>@lang('server.users.new.decompress_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.decompress_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['create-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="create-files" />
                                    <strong>@lang('server.users.new.create_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.create_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['upload-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="upload-files" />
                                    <strong>@lang('server.users.new.upload_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.upload_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['delete-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="delete-files" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.delete_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.delete_files.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['download-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="download-files" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.download_files.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.download_files.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.task_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['list-tasks']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="list-tasks" />
                                    <strong>@lang('server.users.new.list_tasks.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.list_tasks.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['view-task']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-task" />
                                    <strong>@lang('server.users.new.view_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_task.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['toggle-task']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="toggle-task" />
                                    <strong>@lang('server.users.new.toggle_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.toggle_task.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['queue-task']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="queue-task" />
                                    <strong>@lang('server.users.new.queue_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.queue_task.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['create-task']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="create-task" />
                                    <strong>@lang('server.users.new.create_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.create_task.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['delete-task']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="delete-task" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.delete_task.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.delete_task.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('server.users.new.db_header')</h3>
                        </div>
                        <div class="box-body">
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['view-databases']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-databases" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.view_databases.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.view_databases.description')</p>
                            </div>
                            <div>
                                    <input name="permissions[]" type="checkbox" @if(isset($permissions['reset-db-password']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="reset-db-password" />
                                    <span class="label label-danger">@lang('strings.danger')</span>
                                    <strong>@lang('server.users.new.reset_db_password.title')</strong>
                                    <p class="text-muted small">@lang('server.users.new.reset_db_password.description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@can('edit-subuser', $server)
</form>
@endcan
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#selectAllCheckboxes').on('click', function () {
                $('input[type=checkbox]').prop('checked', true);
            });
            $('#unselectAllCheckboxes').on('click', function () {
                $('input[type=checkbox]').prop('checked', false);
            });
        })
    </script>
@endsection
