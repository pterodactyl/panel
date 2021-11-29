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
                            <label class="control-label" for="pStatus">Status</label>
                            <div>
                                <select class="form-control" name="oauth:enabled" id="pStatus">
                                    <option value="true">Enabled</option>
                                    <option value="false" @if(old('oauth:enabled', config('oauth.enabled') == 0)) selected @endif>Disabled</option>
                                </select>
                                <p class="text-muted small">If enabled, login from OAuth sources will be enabled.</p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Require OAuth Authentication</label>
                            <div>
                                <div class="btn-group" data-toggle="buttons">
                                    @php
                                        $level = old('oauth:required', config('oauth.required'));
                                    @endphp
                                    <label class="btn btn-primary @if ($level == 0) active @endif">
                                        <input type="radio" name="oauth:required" autocomplete="off" value="0" @if ($level == 0) checked @endif> Not Required
                                    </label>
                                    <label class="btn btn-primary @if ($level == 1) active @endif">
                                        <input type="radio" name="oauth:required" autocomplete="off" value="1" @if ($level == 1) checked @endif> Users Only
                                    </label>
                                    <label class="btn btn-primary @if ($level == 2) active @endif">
                                        <input type="radio" name="oauth:required" autocomplete="off" value="2" @if ($level == 2) checked @endif> Admin Only
                                    </label>
                                    <label class="btn btn-primary @if ($level == 3) active @endif">
                                        <input type="radio" name="oauth:required" autocomplete="off" value="3" @if ($level == 3) checked @endif> All Users
                                    </label>
                                </div>
                                <p class="text-muted"><small>If enabled, any account falling into the selected grouping will be required to authenticate using OAuth.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label" for="pDisableOtherOptions">Disable Other Authentication Options If Required</label>
                            <div>
                                <select class="form-control" name="oauth:disable_other_authentication_if_required" id="pDisableOtherOptions">
                                    <option value="true">Enabled</option>
                                    <option value="false" @if(old('oauth:disable_other_authentication_if_required', config('oauth.disable_other_authentication_if_required') == 0)) selected @endif>Disabled</option>
                                </select>
                                <p class="text-muted"><small>If enabled, any account falling into the grouping specified before will be required to authenticate using OAuth and will not be able to login using other authentication options.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {{ csrf_field() }}
                    <button class="btn btn-sm btn-primary pull-right" id="saveButton">Save</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Driver Settings</h3>
                    <div class="box-tools">
                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#newDriverModal">Add New</button>
                        <button class="btn btn-sm btn-primary" id="saveButton2">Save</button>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
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
                            <th class="text-center">Listener</th>
                        </tr>
                        @foreach(json_decode($drivers, true) as $driver => $options)
                            <tr>
                                <td>{{ $driver }}</td>
                                <td class="text-center"><input type="checkbox" class="inline-block" name="{{ 'oauth:driver:' . $driver . ':enabled' }}"  @if ($options['enabled']) checked @endif></td>
                                <td><input type="text" class="form-control" name="{{ 'oauth:driver:' . $driver . ':client_id' }}" value="{{ old('oauth:driver:' . $driver . ':client_id', $options['client_id']) }}"></td>
                                <td><input type="password" class="form-control" name="{{ 'oauth:driver:' . $driver . ':client_secret' }}" value="{{ old('oauth:driver:' . $driver . ':client_secret') }}"></td>
                                @if (array_has($options, 'listener'))
                                    <td><input type="text" class="form-control" name="{{ 'oauth:driver:' . $driver . ':listener' }}" value="{{ old('oauth:driver:' . $driver . ':listener', array_has($options, 'listener') ? $options['listener'] : '') }}"></td>
                                @else
                                    <td class="text-center">built-in</td>
                                @endif
                                @if (array_has($options, 'custom'))
                                    <td class="align-middle">
                                        <button name="action" value="delete" class="btn btn-sm btn-danger pull-left muted muted-hover delete-driver" driver="{{$driver}}"><i class="fa fa-trash-o"></i></button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="newDriverModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add New Driver</h4>
                </div>
                <div class="modal-body">
                    <p>Before adding a new driver you must first install the matching <a href="https://socialiteproviders.com/usage/#_1-installation" target="_blank">Socialite Driver</a>.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label" for="pDriverId">Driver ID</label>
                            <div>
                                <input required type="text" class="form-control" name="pDriverId" id="pDriverId"/>
                                <p class="text-muted small">This must be the <b>exact</b> same as the Socialite Driver (usually in lowercase).</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pClientId">Client ID</label>
                            <div>
                                <input required type="text" class="form-control" name="pClientId" id="pClientId"/>
                                <p class="text-muted small">The client id obtained from your OAuth provider.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label" for="pClientSecret">Client Secret</label>
                            <div>
                                <input required type="password" class="form-control" name="pClientSecret" id="pClientSecret"/>
                                <p class="text-muted small">The client secret obtained from your OAuth provider.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pDriverListener">Driver Listener</label>
                            <div>
                                <input required type="text" class="form-control" name="pDriverListener" id="pDriverListener"/>
                                <p class="text-muted small">The Socialite Driver Listener (usually written in the code block under section <code>3. Event Listener</code> on the driver page).</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <p>To add an icon on the login page please add the image as an svg in the folder <code>public/assets/svgs/&lt;driver id&gt;.svg</code></p>
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
                    <button type="button" id="add-modal-save" class="btn btn-success btn-sm">Create</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        let drivers = {!! $drivers !!};

        function saveSettings() {
            for(const driver in drivers) {
                drivers[driver]['enabled'] = $('input[name="oauth:driver:' + driver + ':enabled"]').is(":checked");
                drivers[driver]['client_id'] = $('input[name="oauth:driver:' + driver + ':client_id"]').val();
                drivers[driver]['client_secret'] = $('input[name="oauth:driver:' + driver + ':client_secret"]').val();

                if (drivers[driver].hasOwnProperty('listener')) {
                    drivers[driver]['listener'] = $('input[name="oauth:driver:' + driver + ':listener"]').val();
                }
            }

            return $.ajax({
                method: 'PATCH',
                url: '/admin/settings/oauth',
                contentType: 'application/json',
                data: JSON.stringify({
                    'oauth:enabled': $('select[name="oauth:enabled"]').val(),
                    'oauth:drivers': JSON.stringify(drivers),
                    'oauth:required': $('input[name="oauth:required"]:checked').val(),
                    'oauth:disable_other_authentication_if_required': $('select[name="oauth:disable_other_authentication_if_required"]').val(),
                }),
                headers: { 'X-CSRF-Token': $('input[name="_token"]').val() }
            }).fail(function (jqXHR) {
                showErrorDialog(jqXHR, 'save');
            });
        }

        function save() {
            saveSettings().done(function () {
                swal({
                    title: 'Success',
                    text: 'OAuth settings have been updated successfully and the queue worker was restarted to apply these changes.',
                    type: 'success'
                });
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
            $('#saveButton').on('click', save);
            $('#saveButton2').on('click', save);
            $('.delete-driver').on('click', function () {
                let driverId = $(this).attr('driver');
                $('tr[driver="' + driverId + '"]').remove();
                delete drivers[driverId];
                save();
            });

            // Empty on load
            $('input[name="pDriverId"]').val('');
            $('input[name="pClientId"]').val('');
            $('input[name="pClientSecret"]').val('');
            $('input[name="pDriverListener"]').val('');

            $('#add-modal-save').on('click', function () {
                let driverId = $('input[name="pDriverId"]').val();
                let clientId = $('input[name="pClientId"]').val();
                let clientSecret = $('input[name="pClientSecret"]').val();
                let driverListener = $('input[name="pDriverListener"]').val();

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
