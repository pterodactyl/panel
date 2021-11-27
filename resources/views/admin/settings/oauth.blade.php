@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'oauth'])

@section('title')
    Settings
@endsection

@section('content-header')
    <h1>OAuth Settings<small>Configure OAuth login methods for Pterodactyl.</small></h1>
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
                                    <tr driver="{{ $driver }}">
                                        <td>{{ $driver }}</td>
                                        <td class="text-center"><input type="checkbox" class="inline-block" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':enabled' }}"  @if ($options['enabled']) checked @endif></td>
                                        <td><input type="text" class="form-control" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':client_id' }}" value="{{ old('pterodactyl:oauth:driver:' . $driver . ':client_id', $options['client_id']) }}"></td>
                                        <td><input type="password" class="form-control" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':client_secret' }}" value="{{ old('pterodactyl:oauth:driver:' . $driver . ':client_secret') }}"></td>
                                        @if (array_has($options, 'listener'))
                                            <td><input type="text" class="form-control" name="{{ 'pterodactyl:oauth:driver:' . $driver . ':listener' }}" value="{{ old('pterodactyl:oauth:driver:' . $driver . ':listener', array_has($options, 'listener') ? $options['listener'] : '') }}"></td>
                                        @else
                                            <td class="text-center">—</td>
                                        @endif
                                        @if (array_has($options, 'custom'))
                                            <td class="align-middle">
                                                <button name="action" value="delete" class="btn btn-sm btn-danger pull-left muted muted-hover delete-driver" driver="{{ $driver }}"><i class="fa fa-trash-o"></i></button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        <button class="btn btn-sm btn-success add-new" id="add-modal-save">Add New</button>
                        <button class="btn btn-sm btn-primary form-save">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="add-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Driver</h5>
                    <button type="button" class="close add-modal-close" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Before adding a new driver you must first install the matching <a href="https://socialiteproviders.netlify.app/" target="_blank">Socialite Driver</a>.</p>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="control-label">Driver ID</label>
                            <div>
                                <input required type="text" class="form-control" name="add-new:driver-id"/>
                                <p class="text-muted small">This must be the <b>exact</b> same as the Socialite Driver (usually in lowercase).</p>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">Client ID</label>
                            <div>
                                <input required type="text" class="form-control" name="add-new:client-id"/>
                                <p class="text-muted small">The client ID obtained from your OAuth provider.</p>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">Client Secret</label>
                            <div>
                                <input required type="password" class="form-control" name="add-new:client-secret"/>
                                <p class="text-muted small">The client Secret obtained from your OAuth provider.</p>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">Driver Listener</label>
                            <div>
                                <input required type="text" class="form-control" name="add-new:driver-listener"/>
                                <p class="text-muted small">The Socialite Driver Listener (usually written in the code block under section <code>3. Event Listener</code> on the driver page).</p>
                            </div>
                        </div>
                    </div>


                    <p>To add an icon on the login page please add the image as an svg in the folder <code>public/assets/svgs/&lt;driver id&gt;.svg</code></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm">Add New</button>
                    <button type="button" class="btn btn-default btn-sm pull-left">Close</button>
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
                if (drivers[driver].hasOwnProperty('custom')) continue;
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


            $('.delete-driver').on('click', function () {
                let driverId = $(this).attr('driver');
                $('tr[driver="' + driverId + '"]').remove();
                delete drivers[driverId];
            });

            $('.add-new').on('click', function () {
                $('#add-modal').modal('show');
            });

            $('.add-modal-close').on('click', function () {
                $('#add-modal').modal('hide');
            });

            // Empty on load
            $('input[name="add-new:driver-id"]').val('');
            $('input[name="add-new:client-id"]').val('');
            $('input[name="add-new:client-secret"]').val('');
            $('input[name="add-new:driver-listener"]').val('');

            $('#add-modal-save').on('click', function () {
                let driverId = $('input[name="add-new:driver-id"]').val();
                let clientId = $('input[name="add-new:client-id"]').val();
                let clientSecret = $('input[name="add-new:client-secret"]').val();
                let driverListener = $('input[name="add-new:driver-listener"]').val();

                drivers[driverId] = {
                    'enabled': true,
                    'client_id': clientId,
                    'client_secret': clientSecret,
                    'listener': driverListener,
                    'custom': true,
                };

                saveSettings().done(function () {
                    location.reload();
                });
            });
        });
    </script>
@endsection
