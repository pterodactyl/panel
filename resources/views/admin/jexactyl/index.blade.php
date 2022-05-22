@extends('layouts.admin')

@section('title')
    Jexactyl Settings
@endsection

@section('content-header')
    <h1>Jexactyl<small>Configure Jexactyl-specific settings for the Panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
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
                <h3 class="box-title">Software Release</h3>
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
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Jexactyl Storefront</h3>
            </div>
            <form action="{{ route('admin.jexactyl.store') }}" method="POST">
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="control-label">Enabled</label>
                            <div>
                                <select name="store:enabled" class="form-control">
                                    <option value="{{ 0 }}" @if(!$enabled) selected @endif>Disabled</option>
                                    <option value="{{ 1 }}" @if($enabled) selected @endif>Enabled</option>
                                </select>
                                <p class="text-muted"><small>Determines whether users can access the store UI.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
