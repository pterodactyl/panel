@extends('layouts.master')

@section('title')
    @lang('base.api.new.header')
@endsection

@section('content-header')
    <h1>@lang('base.api.new.header')<small>@lang('base.api.new.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('account.api') }}">@lang('navigation.account.api_access')</a></li>
        <li class="active">@lang('strings.new')</li>
    </ol>
@endsection

@section('footer-scripts')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            $('#selectAllCheckboxes').on('click', function () {
                $('input[type=checkbox]').prop('checked', true);
            });
            $('#unselectAllCheckboxes').on('click', function () {
                $('input[type=checkbox]').prop('checked', false);
            });
        })
    </script>
@endsection

@section('content')
<form action="{{ route('account.api.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <div class="box-title">@lang('base.api.new.form_title')</div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-xs-12 col-lg-6">
                            <label>@lang('base.api.new.descriptive_memo.title')</label>
                            <input type="text" name="memo" class="form-control" name />
                            <p class="help-block">@lang('base.api.new.descriptive_memo.description')</p>
                        </div>
                        <div class="form-group col-xs-12 col-lg-6">
                            <label>@lang('base.api.new.allowed_ips.title')</label>
                            <textarea name="allowed_ips" class="form-control" name></textarea>
                            <p class="help-block">@lang('base.api.new.allowed_ips.description')</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            {!! csrf_field() !!}
                            <button class="btn btn-success pull-right">@lang('strings.create') &rarr;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
