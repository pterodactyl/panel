{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

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
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Panel Settings</h3>
            </div>
            <form action="{{ route('admin.settings') }}" method="POST">
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="control-label">Company Name:</label>
                            <div>
                                <input type="text" class="form-control" name="company" value="{{ old('company', Settings::get('company')) }}" />
                                <p class="text-muted"><small>This is the name that is used throughout the panel and in emails sent to clients.</small></p>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">2FA Required</label>
                            <div>
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-primary @if (old('2fa', Settings::get('2fa', 0)) == 0) active @endif">
                                        <input type="radio" name="2fa" autocomplete="off" value="0" @if (old('2fa', Settings::get('2fa', 0)) == 0) checked @endif> Nobody
                                    </label>
                                    <label class="btn btn-primary @if (old('2fa', Settings::get('2fa', 0)) == 1) active @endif">
                                        <input type="radio" name="2fa" autocomplete="off" value="1" @if (old('2fa', Settings::get('2fa', 0)) == 1) checked @endif> Admins
                                    </label>
                                    <label class="btn btn-primary @if (old('2fa', Settings::get('2fa', 0)) == 2) active @endif">
                                        <input type="radio" name="2fa" autocomplete="off" value="2" @if (old('2fa', Settings::get('2fa', 0)) == 2) checked @endif> Everybody
                                    </label>
                                </div>
                                <p class="text-muted"><small>For improved security you can require all administrators to have 2-Factor authentication enabled, or even require it for all users on the Panel.</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">In order to modify your SMTP settings for sending mail you will need to run <code>php artisan p:environment:mail</code> in this project's root folder.</div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Modify Settings">
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
