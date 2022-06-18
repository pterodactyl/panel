@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'index'])

@section('title')
    Jexactyl Settings
@endsection

@section('content-header')
    <h1>Jexactyl Settings<small>Configure Jexactyl-specific settings for the Panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="box
                @if($version->isLatestPanel())
                    box-success
                @else
                    box-danger
                @endif
            ">
                <div class="box-header with-border">
                    <h3 class="box-title">Software Release <small>Verify Jexactyl is up-to-date.</small></h3>
                </div>
                <div class="box-body">
                    @if ($version->isLatestPanel())
                        You are running Jexactyl <code>{{ config('app.version') }}</code>. 
                    @else
                        Jexactyl is not up-to-date. Latest release is <a href="https://github.com/jexactyl/jexactyl/releases/v{{ $version->getPanel() }}" target="_blank"><code>{{ $version->getPanel() }}</code></a>.
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
