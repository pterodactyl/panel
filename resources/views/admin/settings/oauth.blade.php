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
    <form action="{{ route('admin.settings.oauth') }}" method="POST">
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
                                            <input type="radio" name="pterodactyl:auth:2fa_required" autocomplete="off" value="0" @if ($level == 0) checked @endif> Not Required
                                        </label>
                                        <label class="btn btn-primary @if ($level == 1) active @endif">
                                            <input type="radio" name="pterodactyl:auth:2fa_required" autocomplete="off" value="1" @if ($level == 1) checked @endif> Users Only
                                        </label>
                                        <label class="btn btn-primary @if ($level == 2) active @endif">
                                            <input type="radio" name="pterodactyl:auth:2fa_required" autocomplete="off" value="2" @if ($level == 2) checked @endif> Admin Only
                                        </label>
                                        <label class="btn btn-primary @if ($level == 3) active @endif">
                                            <input type="radio" name="pterodactyl:auth:2fa_required" autocomplete="off" value="2" @if ($level == 3) checked @endif> All Users
                                        </label>
                                    </div>
                                    <p class="text-muted"><small>If enabled, any account falling into the selected grouping will be required to authenticate using OAuth.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save</button>
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
                                            <td class="text-center"><input type="checkbox" class="inline-block" @if ($options['enabled']) checked @endif></td>
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
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save</button>
                    </div>
                </div>
            </div>
        </div>
        {!! csrf_field() !!}
    </form>
@endsection
