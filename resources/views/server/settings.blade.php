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
    Server Settings
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Server Settings</h3><hr />
    <ul class="nav nav-tabs tabs_with_panel" id="config_tabs">
        @can('view-sftp', $server)<li class="active"><a href="#tab_sftp" data-toggle="tab">SFTP Settings</a></li>@endcan
        @can('view-startup', $server)<li><a href="#tab_startup" data-toggle="tab">Startup Configuration</a></li>@endcan
        @can('view-databases', $server)<li><a href="#tab_databases" data-toggle="tab">Databases</a></li>@endcan
    </ul>
    <div class="tab-content">
        @can('view-sftp', $server)
            <div class="tab-pane active" id="tab_sftp">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">SFTP Connection Address:</label>
                                <div>
                                    <input type="text" readonly="readonly" class="form-control" value="{{ $node->fqdn }}:{{ $node->daemonSFTP }}" />
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">SFTP Username:</label>
                                <div>
                                    <input type="text" readonly="readonly" class="form-control" value="{{ $server->username }}" />
                                </div>
                            </div>
                        </div>
                        @can('reset-sftp', $server)
                            <form action="{{ route('server.settings.sftp', $server->uuidShort) }}" method="POST">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="gen_pass" class=" alert alert-success" style="display:none;margin-bottom: 10px;"></div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="control-label">New SFTP Password:</label>
                                        <div>
                                            <input type="password" name="sftp_pass" class="form-control" />
                                            <p class="text-muted"><small>Passwords must meet the following requirements: at least one uppercase character, one lowercase character, one digit, and be at least 8 characters in length. <a href="#" data-action="generate-password">Click here</a> to generate one to use.</small></p>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="control-label">&nbsp;</label>
                                        <div>
                                            {!! csrf_field() !!}
                                            <input type="submit" class="btn btn-sm btn-primary" value="Update Password" />
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @endcan
        @can('view-startup', $server)
            <div class="tab-pane" id="tab_startup">
                <form action="{{ route('server.settings.startup', $server->uuidShort) }}" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="control-label">Startup Command:</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">{{ $service->executable }}</span>
                                        <input type="text" class="form-control" readonly="readonly" value="{{ $processedStartup }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        @can('edit-startup', $server)
                            <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                            <div class="panel-body">
                                <div class="row">
                                    @foreach($variables as $item)
                                        <div class="form-group col-md-6">
                                            <label class="control-label">
                                                @if($item->required === 1)<span class="label label-primary">Required</span> @endif
                                                {{ $item->name }}
                                            </label>
                                            <div>
                                                <input type="text"
                                                    @if($item->user_editable === 1)
                                                        name="{{ $item->env_variable }}"
                                                    @else
                                                        readonly="readonly"
                                                    @endif
                                                class="form-control" value="{{ old($item->env_variable, $item->a_serverValue) }}" data-action="matchRegex" data-regex="{{ $item->regex }}" />
                                            </div>
                                            <p class="text-muted"><small>{{ $item->description }}<br />Regex: <code>{{ $item->regex }}</code><br />Access as: <code>&#123;&#123;{{$item->env_variable}}&#125;&#125;</code></small></p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="panel-heading" style="border-top: 1px solid #ddd;"></div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        {!! csrf_field() !!}
                                        <input type="submit" class="btn btn-primary btn-sm" value="Update Startup Arguments" />
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>
                </form>
            </div>
        @endcan
        @can('view-databases', $server)
            <div class="tab-pane" id="tab_databases">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        @if(count($databases) > 0)
                            <table class="table table-bordered table-hover" style="margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>Database</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>DB Server</th>
                                    </th>
                                </thead>
                                <tbody>
                                    @foreach($databases as $database)
                                        <tr>
                                            <td>{{ $database->database }}</td>
                                            <td>{{ $database->username }}</td>
                                            <td><code>{{ Crypt::decrypt($database->password) }}</code> @can('reset-db-password', $server)<a href="#" data-action="reset-database-password" data-id="{{ $database->id }}"><i class="fa fa-refresh pull-right"></i></a>@endcan</td>
                                            <td><code>{{ $database->a_host }}:{{ $database->a_port }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info" style="margin-bottom:0;">
                                There are no databases configured for this server.
                                @if(Auth::user()->root_admin === 1)
                                    <a href="{{ route('admin.servers.view', [
                                        'id' => $server->id,
                                        'tab' => 'tab_database'
                                    ]) }}" target="_blank">Add a new database.</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
<script>
$(document).ready(function () {
    $('.server-settings').addClass('active');
    $('[data-action="matchRegex"]').keyup(function (event) {
        if (!$(this).data('regex')) return;
        var input = $(this).val();
        console.log(escapeRegExp($(this).data('regex')));
        var regex = new RegExp(escapeRegExp($(this).data('regex')));
        console.log(regex);
        if (!regex.test(input)) {
            $(this).parent().parent().removeClass('has-success').addClass('has-error');
        } else {
            $(this).parent().parent().removeClass('has-error').addClass('has-success');
        }
    });
    $('[data-action="generate-password"]').click(function(e){
        e.preventDefault();
        $.ajax({
            type: "GET",
            url: "/password-gen/12",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
           },
            success: function(data) {
                $("#gen_pass").html('<strong>Generated Password:</strong> ' + data).slideDown();
                $('input[name="sftp_pass"]').val(data);
                return false;
            }
        });
        return false;
    });
    $('[data-action="reset-database-password"]').click(function (e) {
        e.preventDefault();
        var block = $(this);
        $(this).find('i').addClass('fa-spin');
        $.ajax({
            type: 'POST',
            url: '{{ route('server.ajax.reset-database-password', $server->uuidShort) }}',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                'database': $(this).data('id')
            }
        }).done(function (data) {
            block.parent().find('code').html(data);
        }).fail(function(jqXHR, textStatus, errorThrown) {
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
        }).always(function () {
            block.find('i').removeClass('fa-spin');
        });
    });
});
</script>
@endsection
