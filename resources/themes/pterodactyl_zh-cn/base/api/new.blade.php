@extends('layouts.master')

@section('title')
    @lang('base.api.new.header')
@endsection

@section('content-header')
    <h1>@lang('base.api.new.header')<small>@lang('base.api.new.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li class="active">@lang('navigation.account.api_access')</li>
        <li class="active">@lang('base.api.new.header')</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="POST" action="{{ route('account.api.new') }}">
            <div class="col-sm-6 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label" for="memoField">Description <span class="field-required"></span></label>
                            <input id="memoField" type="text" name="memo" class="form-control" value="{{ old('memo') }}">
                        </div>
                        <p class="text-muted">Set an easy to understand description for this API key to help you identify it later on.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label" for="allowedIps">Allowed Connection IPs <span class="field-optional"></span></label>
                            <textarea id="allowedIps" name="allowed_ips" class="form-control" rows="5">{{ old('allowed_ips') }}</textarea>
                        </div>
                        <p class="text-muted">If you would like to limit this API key to specific IP addresses enter them above, one per line. CIDR notation is allowed for each IP address. Leave blank to allow any IP address.</p>
                    </div>
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-success btn-sm pull-right">Create</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
