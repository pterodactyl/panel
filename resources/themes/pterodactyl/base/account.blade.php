{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('base.account.header')
@endsection

@section('scripts')
    @parent
    @if (config('oauth2.enabled'))
        <style>
            .modal {
                text-align: center;
            }
            @media screen and (min-width: 768px) {
                .modal:before {
                    display: inline-block;
                    vertical-align: middle;
                    content: " ";
                    height: 100%;
                }
            }
            .modal-dialog {
                display: inline-block;
                text-align: left;
                vertical-align: middle;
            }
        </style>
        @foreach($enabled_providers as $provider => $value)
            @if (!empty($value['widget_css']))
                <style>
                    {{{ $value['widget_css'] }}}
                </style>
            @endif
        @endforeach
    @endif
@endsection

@section('content-header')
    <h1>@lang('base.account.header')<small>@lang('base.account.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li class="active">@lang('strings.account')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.account.update_pass')</h3>
                    </div>
                    <form action="{{ route('account') }}" method="post">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="current_password" class="control-label">@lang('base.account.current_password')</label>
                                <div>
                                    <input type="password" class="form-control" name="current_password" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="new_password" class="control-label">@lang('base.account.new_password')</label>
                                <div>
                                    <input type="password" class="form-control" name="new_password" />
                                    <p class="text-muted small no-margin">@lang('auth.password_requirements')</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="new_password_again" class="control-label">@lang('base.account.new_password_again')</label>
                                <div>
                                    <input type="password" class="form-control" name="new_password_confirmation" />
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <input type="hidden" name="do_action" value="password" />
                            <input type="submit" class="btn btn-primary btn-sm" value="@lang('base.account.update_pass')" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <form action="{{ route('account') }}" method="POST">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('base.account.update_identity')</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label for="first_name" class="control-label">@lang('base.account.first_name')</label>
                                    <div>
                                        <input type="text" class="form-control" name="name_first" value="{{ Auth::user()->name_first }}" />
                                    </div>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label for="last_name" class="control-label">@lang('base.account.last_name')</label>
                                    <div>
                                        <input type="text" class="form-control" name="name_last" value="{{ Auth::user()->name_last }}" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-xs-12">
                                    <label for="password" class="control-label">@lang('strings.username')</label>
                                    <div>
                                        <input type="text" class="form-control" name="username" value="{{ Auth::user()->username }}" />
                                        <p class="text-muted small no-margin">@lang('base.account.username_help', [ 'requirements' => '<code>a-z A-Z 0-9 _ - .</code>'])</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-xs-12">
                                    <label for="language" class="control-label">@lang('base.account.language')</label>
                                    <div>
                                        <select name="language" id="language" class="form-control">
                                            @foreach($languages as $key => $value)
                                                <option value="{{ $key }}" {{ Auth::user()->language !== $key ?: 'selected' }}>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @if(config('oauth2.enabled'))
                                <div class="row">
                                    <div class="form-group col-xs-12">
                                        <label for="oauth2_id" class="control-label">OAuth2 <span class="field-optional"></span></label>
                                        <div>
                                            <button id="oauth2-edit" class="btn btn-primary">@lang('base.account.oauth2_edit')</button>
                                            <button id="oauth2-advanced" class="btn btn-danger pull-right">@lang('base.account.oauth2_advanced')</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="oauth2-advanced-row" class="row hidden">
                                    <div class="form-group col-xs-12">
                                        <label for="oauth2_id" class="control-label">OAuth2 ID <code>@lang('base.account.oauth2_pattern')</code> <span>{{ implode(',', array_keys($enabled_providers)) }}</span> <span class="field-optional"></span></label>
                                        <div>
                                            <input readonly type="text" name="oauth2_id" value="{{ Auth::user()->getAttribute('oauth2_id') }}" class="form-control form-autocomplete-stop">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="box-footer with-border">
                            {!! csrf_field() !!}
                            <input type="hidden" name="do_action" value="identity" />
                            <button type="submit" class="btn btn-sm btn-primary pull-right">@lang('base.account.update_identity')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.account.update_email')</h3>
                    </div>
                    <form action="{{ route('account') }}" method="post">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="new_email" class="control-label">@lang('base.account.new_email')</label>
                                <div>
                                    <input type="email" class="form-control" name="new_email" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password" class="control-label">@lang('base.account.current_password')</label>
                                <div>
                                    <input type="password" class="form-control" name="current_password" />
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <input type="hidden" name="do_action" value="email" />
                            <input type="submit" class="btn btn-primary btn-sm" value="@lang('base.account.update_email')" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@if (config('oauth2.enabled'))
    <div class="modal fade" id="modal-edit">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    <h4 id="modal-edit-title" class="modal-title">@lang('base.account.oauth2_modal.title')</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="row">
                            <div class="callout callout-info">
                                <p>@lang('base.account.oauth2_modal.info')</p>
                            </div>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped">
                        <tbody>
                        <tr>
                            <th>@lang('base.account.oauth2_modal.table_headers.provider')</th>
                            <th class="text-center">@lang('base.account.oauth2_modal.table_headers.name')</th>
                            <th class="text-center">@lang('base.account.oauth2_modal.table_headers.id')</th>
                        </tr>
                        @foreach($enabled_providers as $provider => $values)
                            <tr>
                                <td>
                                    @if (empty($oauth2_ids[$provider]))
                                        <form id="link-oauth2-{{ $provider }}" action="{{ route('account') }}" method="post">
                                            {!! csrf_field() !!}
                                            <input type="hidden" name="do_action" value="oauth2_link" />
                                            <input type="hidden" name="oauth2_driver" value="{{ $provider }}">
                                            <div data-toggle="tooltip" title="@lang('base.account.oauth2_modal.hover')" onclick="console.log('click');$('#link-oauth2-{{ $provider }}').submit();" style="cursor: pointer">
                                                {!! $values['widget_html'] !!}
                                            </div>
                                        </form>
                                    @else
                                        {!! $values['widget_html'] !!}
                                    @endif
                                </td>
                                <td>
                                    {{ \Illuminate\Support\Str::ucfirst($provider) }}
                                </td>
                                <td>
                                    @if (empty($oauth2_ids[$provider]))
                                        <code>{{ empty($oauth2_ids[$provider]) ? '' : $oauth2_ids[$provider] }}</code>
                                    @else
                                        <form id="unlink-oauth2-{{ $provider }}" action="{{ route('account') }}" method="post">
                                            {!! csrf_field() !!}
                                            <input type="hidden" name="do_action" value="oauth2_unlink" />
                                            <input type="hidden" name="oauth2_driver" value="{{ $provider }}">
                                            <code>{{ empty($oauth2_ids[$provider]) ? '' : $oauth2_ids[$provider] }}</code> <button onclick="$('#unlink-oauth2-{{ $provider }}').submit();" class="btn btn-sm btn-danger pull-right">Unlink</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">@lang('base.account.oauth2_modal.close')</button>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@section('footer-scripts')
    @parent
    @if (config('oauth2.enabled'))
        <script>
            $(document).tooltip({
                selector: '[data-toggle="tooltip"]'
            });
            $('#oauth2-advanced').click(function (event) {
                event.preventDefault();
                let row =  $('#oauth2-advanced-row');
                if (row.hasClass('hidden')) row.removeClass('hidden');
                else row.addClass('hidden');
            });
            $('#oauth2-edit').click(function (event) {
                event.preventDefault();
                $('#modal-edit').modal({
                    show: true,
                });
            });
        </script>
    @endif
@endsection
