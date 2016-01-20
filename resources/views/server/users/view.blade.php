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
@extends('layouts.master')

@section('title')
    Manage Subuser: {{ $subuser->a_userEmail }}
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Manage Subuser <span class="label label-primary">{{ $subuser->a_userEmail }}</span></h3><hr />
    @can('edit-subuser', $server)
        <form action="{{ route('server.subusers.view', ['uuid' => $server->uuidShort, 'id' => md5($subuser->id) ])}}" method="POST">
    @endcan
        <div class="row">
            <div class="col-md-6 fuelux">
                <h4>Power Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['power-start']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-start"> <strong>Start Server</strong>
                        <p class="text-muted"><small>Allows user to start server.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['power-stop']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-stop"> <strong>Stop Server</strong>
                        <p class="text-muted"><small>Allows user to stop server.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['power-restart']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-restart"> <strong>Restart Server</strong>
                        <p class="text-muted"><small>Allows user to restart server. A user with this permission can stop or start a server even without the above permissions.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['power-kill']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="power-kill"> <strong>Kill Server</strong>
                        <p class="text-muted"><small>Allows user to kill server process.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['send-command']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="send-command"> <strong>Send Console Command</strong>
                        <p class="text-muted"><small>Allows sending a command from the console. If the user does not have stop or restart permissions they cannot send the application's stop command.</small><p>
                    </label>
                </div>
            </div>
            <div class="col-md-6 fuelux">
                <h4>File Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['list-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="list-files"> <strong>List Files</strong>
                        <p class="text-muted"><small>Allows user to list all files and folders on the server but not view file contents.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['edit-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="edit-files"> <strong>Edit Files</strong>
                        <p class="text-muted"><small>Allows user to open a file for <em>viewing only</em>.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['save-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="save-files"> <strong>Save Files</strong>
                        <p class="text-muted"><small>Allows user to save modified file contents.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['add-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="add-files"> <strong>Create Files</strong>
                        <p class="text-muted"><small>Allows user to create a new file within the panel.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['upload-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="upload-files"> <strong>Upload Files</strong>
                        <p class="text-muted"><small>Allows user to upload files.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['delete-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="delete-files"> <strong>Delete Files</strong>
                        <p class="text-muted"><small><span class="label label-danger">Danger</span> Allows user to delete files from the system.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['download-files']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="download-files"> <strong>Download Files</strong>
                        <p class="text-muted"><small><span class="label label-danger">Danger</span> Allows user to download files. If a user is given this permission they can download and view file contents.</small><p>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 fuelux">
                <h4>Subuser Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['list-subusers']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="list-subusers"> <strong>List Subusers</strong>
                        <p class="text-muted"><small>Allows user to view all subusers assigned to the server.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['view-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-subuser"> <strong>View Subuser</strong>
                        <p class="text-muted"><small>Allows user to view specific subuser permissions.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['edit-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="edit-subuser"> <strong>Edit Subuser</strong>
                        <p class="text-muted"><small>Allows user to modify permissions for a subuser. <em>They will not have permission to modify themselves.</em></small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['create-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="create-subuser"> <strong>Create Subuser</strong>
                        <p class="text-muted"><small>Allows a user to create a new subuser.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['delete-subuser']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="delete-subuser"> <strong>Delete Subuser</strong>
                        <p class="text-muted"><small>Allows a user to delete a subuser.</small><p>
                    </label>
                </div>
            </div>
            <div class="col-md-6 fuelux">
                <h4>Server Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['set-connection']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="set-connection"> <strong>Set Default Connection</strong>
                        <p class="text-muted"><small>Allows user to set the default connection used for a server as well as view avaliable ports.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['view-startup']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-startup"> <strong>View Startup Command</strong>
                        <p class="text-muted"><small>Allows user to view the startup command and associated variables for a server.</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['edit-startup']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="edit-startup"> <strong>Edit Startup Command</strong>
                        <p class="text-muted"><small>Allows a user to modify startup variables for a server.</small><p>
                    </label>
                </div>
                <h4>SFTP Management</h4><hr />
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['view-sftp']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="view-sftp"> <strong>View SFTP Details</strong>
                        <p class="text-muted"><small>Allows user to view the server's SFTP information (not the password).</small><p>
                    </label>
                </div>
                <div class="checkbox highlight">
                    <label class="checkbox-custom highlight" data-initialize="checkbox">
                        <input class="sr-only" name="permissions[]" type="checkbox" @if(isset($permissions['reset-sftp']))checked="checked"@endif @cannot('edit-subuser', $server)disabled="disabled"@endcannot value="reset-sftp"> <strong>Reset SFTP Password</strong>
                        <p class="text-muted"><small>Allows user to change the SFTP password for the server.</small><p>
                    </label>
                </div>
            </div>
        </div>
    @can('edit-subuser', $server)
        <div class="well">
            <div class="row">
                <div class="col-md-12">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Modify Subuser" />
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
<script>
$(document).ready(function () {
    $('.server-users').addClass('active');
});
</script>
@endsection
