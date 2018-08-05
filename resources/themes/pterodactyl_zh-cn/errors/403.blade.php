{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.error')

@section('title')
    @lang('base.errors.403.header')
@endsection

@section('content-header')
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1 col-xs-12">
        <div class="box box-danger">
            <div class="box-body text-center">
                <h1 class="text-red" style="font-size: 160px !important;font-weight: 100 !important;">403</h1>
                <p class="text-muted">@lang('base.errors.403.desc')</p>
            </div>
            <div class="box-footer with-border">
                <a href="{{ URL::previous() }}"><button class="btn btn-danger">&larr; @lang('base.errors.return')</button></a>
                <a href="/"><button class="btn btn-default">@lang('base.errors.home')</button></a>
            </div>
        </div>
    </div>
</div>
@endsection
