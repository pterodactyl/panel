@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'oauth'])

@section('title')
    Settings
@endsection

@section('content-header')
    <h1>Panel Settings<small>Configure Pterodactyl to your liking.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">OAuth Settings</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="control-label">Status</label>
                            <div>
                                <select class="form-control" name="pterodactyl:auth:oauth:enabled">
                                    <option value="true">Enabled</option>
                                    <option value="false" @if(old('pterodactyl:auth:oauth:enabled', config('pterodactyl.auth.oauth.enabled') == 0)) selected @endif>Disabled</option>
                                </select>
                                <p class="text-muted small">If enabled, login from OAuth sources will be enabled.</p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Require OAuth Authentication</label>
                            <div>
                                <div class="btn-group" data-toggle="buttons">
                                    @php
                                        $level = old('pterodactyl:auth:oauth:required', config('pterodactyl.auth.oauth.required'));
                                    @endphp
                                    <label class="btn btn-primary @if ($level == 0) active @endif">
                                        <input type="radio" name="pterodactyl:auth:oauth:required" autocomplete="off" value="0" @if ($level == 0) checked @endif> Not Required
                                    </label>
                                    <label class="btn btn-primary @if ($level == 1) active @endif">
                                        <input type="radio" name="pterodactyl:auth:oauth:required" autocomplete="off" value="1" @if ($level == 1) checked @endif> Users Only
                                    </label>
                                    <label class="btn btn-primary @if ($level == 2) active @endif">
                                        <input type="radio" name="pterodactyl:auth:oauth:required" autocomplete="off" value="2" @if ($level == 2) checked @endif> Admin Only
                                    </label>
                                    <label class="btn btn-primary @if ($level == 3) active @endif">
                                        <input type="radio" name="pterodactyl:auth:oauth:required" autocomplete="off" value="3" @if ($level == 3) checked @endif> All Users
                                    </label>
                                </div>
                                <p class="text-muted"><small>If enabled, any account falling into the selected grouping will be required to authenticate using OAuth.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Disable Other Authentication Options If Required</label>
                            <div>
                                <select class="form-control" name="pterodactyl:auth:oauth:disable_other_authentication_if_required">
                                    <option value="true">Enabled</option>
                                    <option value="false" @if(old('pterodactyl:auth:oauth:disable_other_authentication_if_required', config('pterodactyl.auth.oauth.disable_other_authentication_if_required') == 0)) selected @endif>Disabled</option>
                                </select>
                                <p class="text-muted"><small>If enabled, any account falling into the grouping specified before will be required to authenticate using OAuth and will not be able to login using other authentication options.</small></p>
                            </div>

                        </div>
                </div>
                <div class="box-footer">
                    <button class="btn btn-sm btn-primary pull-right form-save">Save</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Driver Settings</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <table class="table">
                                <tr>
                                    <th>Driver</th>
                                    <th class="text-center">Status</th>
                                    <th>Client ID</th>
                                    <th>
                                        <div>
                                            Client Secret
                                            <p class="text-muted"><small>Leave blank to continue using the existing client secret.</small></p>
                                        </div>
                                    </th>
                                    <th>
                                        <div>
                                            Listener
                                            <p class="text-muted"><small>Facebook, Twitter, LinkedIn, Google, Github, GitLab and BitBucket do not require one.</small></p>
                                        </div>
                                    </th>
                                </tr>
                                @foreach(json_decode($drivers, true) as $driver => $options)
                                    <tr>
                                        <td>{{ $driver }}</td>
                                        <td class="text-center"><input type="checkbox" class="inline-block" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':enabled' }}"  @if ($options['enabled']) checked @endif></td>
                                        <td><input type="text" class="form-control" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':client_id' }}" value="{{ old('pterodactyl:oauth:driver:' . $driver . ':client_id', $options['client_id']) }}"></td>
                                        <td><input type="password" class="form-control" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':client_secret' }}" value="{{ old('pterodactyl:oauth:driver:' . $driver . ':client_secret') }}"></td>
                                        @if (array_has($options, 'listener'))
                                            <td><input type="text" class="form-control" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':listener' }}" value="{{ old('pterodactyl:oauth:driver:' . $driver . ':listener', array_has($options, 'listener') ? $options['listener'] : '') }}"></td>
                                        @else
                                            <td class="text-center">—</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button class="btn btn-sm btn-primary pull-right form-save">Save</button>
                </div>
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('footer-scripts')
    @parent

    <script>
        let drivers = {!! $drivers !!};

        function saveSettings() {
            for(driver in drivers) {
                drivers[driver]['enabled'] = $('input[name="pterodactyl:oauth:driver:' + driver + ':enabled"]').is(":checked");
                drivers[driver]['client_id'] = $('input[name="pterodactyl:oauth:driver:' + driver + ':client_id"]').val();
                drivers[driver]['client_secret'] = $('input[name="pterodactyl:oauth:driver:' + driver + ':client_secret"]').val();
                if (drivers[driver].hasOwnProperty('listener')) {
                    drivers[driver]['listener'] = $('input[name="pterodactyl:oauth:driver:' + driver + ':listener"]').val();
                }
            }

            return $.ajax({
                method: 'PATCH',
                url: '/admin/settings/oauth',
                contentType: 'application/json',
                data: JSON.stringify({
                    'pterodactyl:auth:oauth:enabled': $('select[name="pterodactyl:auth:oauth:enabled"]').val(),
                    'pterodactyl:auth:oauth:drivers': JSON.stringify(drivers),
                    'pterodactyl:auth:oauth:required': $('input[name="pterodactyl:auth:oauth:required"]:checked').val(),
                    'pterodactyl:auth:oauth:disable_other_authentication_if_required': $('select[name="pterodactyl:auth:oauth:disable_other_authentication_if_required"]').val(),
                }),
                headers: { 'X-CSRF-Token': $('input[name="_token"]').val() }
            }).fail(function (jqXHR) {
                showErrorDialog(jqXHR, 'save');
            });
        }

        function showErrorDialog(jqXHR, verb) {
            console.error(jqXHR);
            let errorText = '';
            if (!jqXHR.responseJSON) {
                errorText = jqXHR.responseText;
            } else if (jqXHR.responseJSON.error) {
                errorText = jqXHR.responseJSON.error;
            } else if (jqXHR.responseJSON.errors) {
                $.each(jqXHR.responseJSON.errors, function (i, v) {
                    if (v.detail) {
                        errorText += v.detail + ' ';
                    }
                });
            }

            swal({
                title: 'Whoops!',
                text: 'An error occurred while attempting to ' + verb + ' oauth settings: ' + errorText,
                type: 'error'
            });
        }

        $(document).ready(function () {
            $('.form-save').on('click', function () {

                saveSettings().done(function () {
                    swal({
                        title: 'Success',
                        text: 'OAuth settings have been updated successfully and the queue worker was restarted to apply these changes.',
                        type: 'success'
                    });
                });
            });
        });
    </script>
@endsection
