@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'mail'])

@section('title')
    @lang('admin/settings.mail.header.title')
@endsection

@section('content-header')
    <h1>@lang('admin/settings.mail.header.overview')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/servers_view.header.admin')</a></li>
        <li class="active">@lang('admin/settings.header.settings')</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/settings.mail.content.email_settings')</h3>
                </div>
                @if($disabled)
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="alert alert-info no-margin-bottom">
                                    @lang('admin/settings.mail.content.email_settings_hint')
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <form>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="control-label">@lang('admin/settings.mail.content.smtp_host')</label>
                                    <div>
                                        <input required type="text" class="form-control" name="mail:host" value="{{ old('mail:host', config('mail.host')) }}" />
                                        <p class="text-muted small">@lang('admin/settings.mail.content.smtp_host_hint')</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="control-label">@lang('admin/settings.mail.content.smtp_port')</label>
                                    <div>
                                        <input required type="number" class="form-control" name="mail:port" value="{{ old('mail:port', config('mail.port')) }}" />
                                        <p class="text-muted small">@lang('admin/settings.mail.content.smtp_port_hint')</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="control-label">@lang('admin/settings.mail.content.encrypt')</label>
                                    <div>
                                        @php
                                            $encryption = old('mail:encryption', config('mail.encryption'));
                                        @endphp
                                        <select name="mail:encryption" class="form-control">
                                            <option value="" @if($encryption === '') selected @endif>@lang('admin/settings.mail.content.none')</option>
                                            <option value="tls" @if($encryption === 'tls') selected @endif>@lang('admin/settings.mail.content.tls')</option>
                                            <option value="ssl" @if($encryption === 'ssl') selected @endif>@lang('admin/settings.mail.content.ssl')</option>
                                        </select>
                                        <p class="text-muted small">@lang('admin/settings.mail.content.encrypt_hint')</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">@lang('admin/settings.mail.content.username') <span class="field-optional"></span></label>
                                    <div>
                                        <input type="text" class="form-control" name="mail:username" value="{{ old('mail:username', config('mail.username')) }}" />
                                        <p class="text-muted small">@lang('admin/settings.mail.content.username_hint')</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">@lang('admin/settings.mail.content.password') <span class="field-optional"></span></label>
                                    <div>
                                        <input type="password" class="form-control" name="mail:password"/>
                                        <p class="text-muted small">@lang('admin/settings.mail.content.password_hint')</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <hr />
                                <div class="form-group col-md-6">
                                    <label class="control-label">@lang('admin/settings.mail.content.mail_from')</label>
                                    <div>
                                        <input required type="email" class="form-control" name="mail:from:address" value="{{ old('mail:from:address', config('mail.from.address')) }}" />
                                        <p class="text-muted small">@lang('admin/settings.mail.content.mail_from_hint')</p>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">@lang('admin/settings.mail.content.mail_from_name') <span class="field-optional"></span></label>
                                    <div>
                                        <input type="text" class="form-control" name="mail:from:name" value="{{ old('mail:from:name', config('mail.from.name')) }}" />
                                        <p class="text-muted small">@lang('admin/settings.mail.content.mail_from_name_hint')</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {{ csrf_field() }}
                            <div class="pull-right">
                                <button type="button" id="testButton" class="btn btn-sm btn-success">@lang('admin/settings.mail.content.test')</button>
                                <button type="button" id="saveButton" class="btn btn-sm btn-primary">@lang('admin/settings.content.save')</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    {!! Theme::js('js/laroute.js?t={cache-version}') !!}
    {!! Theme::js('vendor/jquery/jquery.min.js?t={cache-version}') !!}
    {!! Theme::js('vendor/sweetalert/sweetalert.min.js?t={cache-version}') !!}

    <script>
        function saveSettings() {
            return $.ajax({
                method: 'PATCH',
                url: Router.route('admin.settings.mail'),
                contentType: 'application/json',
                data: JSON.stringify({
                    'mail:host': $('input[name="mail:host"]').val(),
                    'mail:port': $('input[name="mail:port"]').val(),
                    'mail:encryption': $('select[name="mail:encryption"]').val(),
                    'mail:username': $('input[name="mail:username"]').val(),
                    'mail:password': $('input[name="mail:password"]').val(),
                    'mail:from:address': $('input[name="mail:from:address"]').val(),
                    'mail:from:name': $('input[name="mail:from:name"]').val()
                }),
                headers: { 'X-CSRF-Token': $('input[name="_token"]').val() }
            }).fail(function (jqXHR) {
                showErrorDialog(jqXHR, 'save');
            });
        }

        function testSettings() {
            swal({
                type: 'info',
                title: '@lang('admin/settings.mail.content.test_settings')',
                text: '@lang('admin/settings.mail.content.test_settings_text')',
                showCancelButton: true,
                confirmButtonText: 'Test',
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'GET',
                    url: Router.route('admin.settings.mail.test'),
                    headers: { 'X-CSRF-Token': $('input[name="_token"]').val() }
                }).fail(function (jqXHR) {
                    showErrorDialog(jqXHR, 'test');
                }).done(function () {
                    swal({
                        title: '@lang('admin/settings.mail.content.success')',
                        text: '@lang('admin/settings.mail.content.success_text')',
                        type: 'success'
                    });
                });
            });
        }

        function saveAndTestSettings() {
            saveSettings().done(testSettings);
        }

        function showErrorDialog(jqXHR, verb) {
            console.error(jqXHR);
            var errorText = '';
            if (!jqXHR.responseJSON) {
                errorText = jqXHR.responseText;
            } else if (jqXHR.responseJSON.error) {
                errorText = jqXHR.responseJSON.error;
            } else if (jqXHR.responseJSON.errors) {
                $.each(jqXHR.responseJSON.errors, function (i, v) {
                    if (v.detail) {
                        errorText += v.detail + ' ';
                    }
                });
            }

            swal({
                title: '@lang('admin/settings.mail.content.ooopsi')',
                text: '@lang('admin/settings.mail.content.ooopsi_textStart') ' + verb + ' @lang('admin/settings.mail.content.ooopsi_textEnd') ' + errorText,
                type: 'error'
            });
        }

        $(document).ready(function () {
            $('#testButton').on('click', saveAndTestSettings);
            $('#saveButton').on('click', function () {
                saveSettings().done(function () {
                    swal({
                        title: '@lang('admin/settings.mail.content.success')',
                        text: '@lang('admin/settings.mail.content.updated_text')',
                        type: 'success'
                    });
                });
            });
        });
    </script>
@endsection
