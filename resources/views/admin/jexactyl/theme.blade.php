@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'theme'])

@section('title')
    Jexactyl Theme
@endsection

@section('content-header')
    <h1>Jexactyl Theme<small>Configure the theme for Jexactyl.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.jexactyl.theme') }}" method="POST">
                <div class="box box-info
                ">
                    <div class="box-header with-border">
                        <h3 class="box-title">Select system theme <small>The selection for Jexactyl's theme.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Enabled</label>
                                <div>
                                    <select name="theme:current" class="form-control">
                                        <option @if ($current == 'default') selected @endif value="default">Default Theme</option>
                                        <option @if ($current == 'dark') selected @endif value="dark">Dark Theme</option>
                                        <option @if ($current == 'light') selected @endif value="light">Light Theme</option>
                                    </select>
                                    <p class="text-muted"><small>Determines the theme for Jexactyl's UI.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
