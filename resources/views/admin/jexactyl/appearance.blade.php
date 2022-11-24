@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'appearance'])

@section('title')
    Theme Settings
@endsection

@section('content-header')
    <h1>Jexactyl Appearance<small>Configure the theme for Jexactyl.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.jexactyl.appearance') }}" method="POST">
            <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">General Settings <small>Configure general appearance settings.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Panel Name</label>
                                <div>
                                    <input type="text" class="form-control" name="app:name" value="{{ old('app:name', config('app.name')) }}" />
                                    <p class="text-muted"><small>This is the name that is used throughout the panel and in emails sent to clients.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Panel Logo</label>
                                <div>
                                    <input type="text" class="form-control" name="app:logo" value="{{ $logo }}" />
                                    <p class="text-muted"><small>The logo which is used for the Panel&apos;s frontend.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Theme Settings <small>The selection for Jexactyl's theme.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Admin Theme</label>
                                <div>
                                    <select name="theme:admin" class="form-control">
                                        <option @if ($admin == 'jexactyl') selected @endif value="jexactyl">Default Theme</option>
                                        <option @if ($admin == 'dark') selected @endif value="dark">Dark Theme</option>
                                        <option @if ($admin == 'light') selected @endif value="light">Light Theme</option>
                                        <option @if ($admin == 'blue') selected @endif value="blue">Blue Theme</option>
                                        <option @if ($admin == 'minecraft') selected @endif value="minecraft">Minecraft&#8482; Theme</option>
                                    </select>
                                    <p class="text-muted"><small>Determines the theme for Jexactyl's Admin UI.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Client Background</label>
                                <div>
                                    <input type="text" class="form-control" name="theme:user:background" value="{{ old('theme:user:background', config('theme.user.background')) }}" />
                                    <p class="text-muted"><small>If you enter a URL here, the client pages will have your image as the page background.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {!! csrf_field() !!}
                <button type="submit" name="_method" value="PATCH" class="btn btn-default pull-right">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
