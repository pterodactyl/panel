@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'oauth2'])

@section('title')
    OAuth2
@endsection

@section('scripts')
    @parent
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
    <style id="modal-widget-css"></style>
@endsection

@section('content-header')
    <h1>{!! __('admin/settings.oauth2.page_title') !!}</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/settings.oauth2.box_title')</h3>
                </div>
                <form id="oauth2-form" action="{{ route('admin.settings.oauth2') }}" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings.oauth2.status.label')</label>
                                <div>
                                    <select class="form-control" name="oauth2:enabled">
                                        <option value="true">@lang('strings.enabled')</option>
                                        <option value="false" @if(old('oauth2:enabled', config('oauth2.enabled')) == '0') selected @endif>@lang('strings.disabled')</option>
                                    </select>
                                    <p class="text-muted small">@lang('admin/settings.oauth2.status.description')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings.oauth2.required.label')</label>
                                <div>
                                    <div class="btn-group" data-toggle="buttons">
                                        @php
                                            $level = old('oauth2.required', config('oauth2.required'));
                                        @endphp
                                        <label class="btn btn-primary @if ($level == 0) active @endif">
                                            <input type="radio" name="oauth2:required" autocomplete="off" value="0" @if ($level == 0) checked @endif> @lang('admin/settings.oauth2.required.options.not_required')
                                        </label>
                                        <label class="btn btn-primary @if ($level == 1) active @endif">
                                            <input type="radio" name="oauth2:required" autocomplete="off" value="1" @if ($level == 1) checked @endif> @lang('admin/settings.oauth2.required.options.admin_only')
                                        </label>
                                        <label class="btn btn-primary @if ($level == 2) active @endif">
                                            <input type="radio" name="oauth2:required" autocomplete="off" value="2" @if ($level == 2) checked @endif> @lang('admin/settings.oauth2.required.options.all_users')
                                        </label>
                                    </div>
                                    <p class="text-muted"><small>@lang('admin/settings.oauth2.required.description')</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings.oauth2.default.label')</label>
                                <div>
                                    <select id="default-provider" class="form-control" name="oauth2:default_driver">
                                        @foreach($providers as $provider => $value)
                                            <option id="default-provider-option-{{ $provider }}" {{ empty($value['client_id']) ? 'hidden' : ''}} value="{{ $provider }}" @if($provider == config('oauth2.default_driver')) selected @endif>{{ \Illuminate\Support\Str::ucfirst($provider) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-muted small">@lang('admin/settings.oauth2.default.description')</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.label')</label>
                                <table id="provider-table" class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th>@lang('admin/settings.oauth2.providers.table_headers.provider') <div class="pull-right"><span data-toggle="create"><span id="create-provider" class="btn btn-xs btn-success" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.create')"><i class="fa fa-plus"></i></span></span></div></th>
                                            <th class="text-center" style="width: 150px">@lang('admin/settings.oauth2.providers.table_headers.action')</th>
                                            <th class="text-center" style="width: 20px">@lang('admin/settings.oauth2.providers.table_headers.status')</th>
                                        </tr>
                                        @php
                                            $default_provider = config('oauth2.default_driver');
                                        @endphp
                                        @foreach($providers as $provider => $value)
                                            <tr id="provider-row-{{ $provider }}">
                                                <td>{{ \Illuminate\Support\Str::ucfirst($provider) }}
                                                    <span id="default-notice-{{ $provider }}" class="{{ $provider == $default_provider ? '' : 'hidden' }}" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.default_provider')"><i class="fa fa-star"></i></span>
                                                    <span id="unset-warning-{{ $provider }}" class="{{ !empty($value['client_id']) ? 'hidden' : '' }}" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.unset_provider')"><i class="fa fa-exclamation-triangle"></i></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="btn btn-xs btn-primary" data-toggle="edit" data-edit="{{ \Illuminate\Support\Str::ucfirst($provider) }}">@lang('admin/settings.oauth2.providers.edit')</span>
                                                    <span id="button-delete-{{ $provider }}" class="btn btn-xs btn-danger {{ $provider == $default_provider ? 'hidden' : '' }}" data-toggle="delete" data-delete="{{ \Illuminate\Support\Str::ucfirst($provider) }}">@lang('admin/settings.oauth2.providers.delete')</span>
                                                </td>
                                                <td class="text-center">
                                                    <input id="provider-state-value-{{ $provider }}" name="oauth2:providers:{{ $provider }}:status" class="hidden" value="{{ $value['status'] == true ? 'true' : 'false' }}">
                                                    <span id="provider-state-on-{{ $provider }}" data-toggle="disable" data-disable="{{ $provider }}" class="btn btn-xs text-green {{ $value['status'] == true && $provider != $default_provider ? '' : 'hidden'}}"><i class="fa fa-check fa-2x"></i></span>
                                                    <span id="provider-state-default-{{ $provider }}" class="btn btn-xs disabled text-green {{ $provider == $default_provider ? '' : 'hidden'}}" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.default_provider_state_notice')"><i class="fa fa-check fa-2x"></i></span>
                                                    <span id="provider-state-off-{{ $provider }}" data-toggle="enable" data-enable="{{ $provider }}" class="btn btn-xs text-red {{ $value['status'] != true && !empty($value['client_id']) && $provider != $default_provider ? '' : 'hidden'}}"><i class="fa fa-times fa-2x"></i></span>
                                                    <span id="provider-state-unset-{{ $provider }}" class="btn btn-xs disabled text-red {{ empty($value['client_id']) && $provider != $default_provider ? '' : 'hidden'}}" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.unset_provider_state_notice')"><i class="fa fa-times fa-2x"></i></span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button id="save" type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save</button>
                    </div>
                    <div id="saved-providers" class="hidden">
                        <input id="saved-all-drivers" value="{{ config('oauth2.all_drivers') }}">
                        <input id="saved-default-provider" value="{{ config('oauth2.default_driver') }}">
                        <input id="saved-new-providers" name="oauth2:providers:new" value="">
                        <input id="saved-deleted-providers" name="oauth2:providers:deleted" value="">
                        @foreach($providers as $provider => $value)
                            <input id="saved-provider-listener-{{ $provider }}" name="oauth2:providers:{{ $provider }}:listener" value="{{{ $value['listener'] }}}">
                            <input id="saved-provider-id-{{ $provider }}" name="oauth2:providers:{{ $provider }}:client_id" value="{{ $value['client_id'] }}">
                            <input id="saved-provider-secret-{{ $provider }}" name="oauth2:providers:{{ $provider }}:client_secret" value="{{ $value['client_secret'] }}">
                            <input id="saved-provider-scopes-{{ $provider }}" name="oauth2:providers:{{ $provider }}:scopes" value="{{ $value['scopes'] }}">
                            <input id="saved-provider-widget-html-{{ $provider }}" name="oauth2:providers:{{ $provider }}:widget_html" value="{{{ $value['widget_html'] }}}">
                            <input id="saved-provider-widget-css-{{ $provider }}" name="oauth2:providers:{{ $provider }}:widget_css" value="{{{ $value['widget_css'] }}}">
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-create">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    <h4 class="modal-title">@lang('admin/settings.oauth2.providers.modal_create_title')</h4>
                </div>
                <form id="modal-create-form" class="modal-body">
                    <div class="box-body">
                        <div class="row">
                            <div class="callout callout-danger">
                                <p>{!! __('admin/settings.oauth2.providers.notice', ['url' => '<code>' . config('app.url') . '/auth/oauth2/callback' . '</code>']) !!}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="callout callout-info">
                                <p>{!! __('admin/settings.oauth2.providers.create_custom_notice', ['url' => '']) !!}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.name') <code class="text-muted">[a-zA-Z0-9\-_]+</code> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-create-name" class="form-control" type="text" pattern="[a-zA-Z0-9\-_]+" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.package') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-create-package" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.listener') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-create-listener" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.id') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-create-id" class="form-control" type="text" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.secret') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-create-secret" class="form-control" type="text" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.scopes') <small class="text-muted">@lang('admin/settings.oauth2.providers.modal.scopes_notice')</small> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-create-scopes" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.widget') <small class="text-muted">html</small> <b class="text-red"><i class="fa fa-exclamation-triangle"></i> {{{ __('admin/settings.oauth2.providers.modal.widget_html_warning')}}}</b> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <textarea id="modal-create-widget-html" class="form-control" rows="5" style="resize: vertical;" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.widget') <small class="text-muted">css</small> <b class="text-red"><i class="fa fa-exclamation-triangle"></i> {{{ __('admin/settings.oauth2.providers.modal.widget_css_warning') }}}</b> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <textarea id="modal-create-widget-css" class="form-control" rows="5" style="resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="control-label">@lang('admin/settings.oauth2.providers.modal.preview')</label>
                            <div id="modal-create-preview"></div>
                        </div>
                    </div>
                    <input id="modal-create-submit" type="submit" class="hidden">
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">@lang('admin/settings.oauth2.providers.modal.close')</button>
                    <button type="button" class="btn btn-primary" id="modal-create-save">@lang('admin/settings.oauth2.providers.modal.save')</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-edit">
        <input id="modal-edit-provider" class="hidden">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    <h4 id="modal-edit-title" class="modal-title"></h4>
                </div>
                <form id="modal-edit-form" class="modal-body">
                    <div class="box-body">
                        <div class="row">
                            <div class="callout callout-danger">
                                <p>{!! __('admin/settings.oauth2.providers.notice', ['url' => '<code>' . config('app.url') . '/auth/oauth2/callback' . '</code>']) !!}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.listener') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-edit-listener" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.id') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-edit-id" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.secret') <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-edit-secret" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.scopes') <small class="text-muted">@lang('admin/settings.oauth2.providers.modal.scopes_notice')</small> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <input id="modal-edit-scopes" class="form-control" type="text">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.widget') <small class="text-muted">html</small> <b class="text-red"><i class="fa fa-exclamation-triangle"></i> {{{ __('admin/settings.oauth2.providers.modal.widget_html_warning')}}}</b> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <textarea id="modal-edit-widget-html" class="form-control" rows="5" style="resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label class="control-label">@lang('admin/settings.oauth2.providers.modal.widget') <small class="text-muted">css</small> <b class="text-red"><i class="fa fa-exclamation-triangle"></i> {{{ __('admin/settings.oauth2.providers.modal.widget_css_warning') }}}</b> <a target="_blank" data-toggle="tooltip" title="@lang('admin/settings.oauth2.providers.modal.help')" href=""><i class="fa fa-question-circle"></i></a></label>
                                <div>
                                    <textarea id="modal-edit-widget-css" class="form-control" rows="5" style="resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="control-label">@lang('admin/settings.oauth2.providers.modal.preview')</label>
                            <div id="modal-edit-preview"></div>
                        </div>
                    </div>
                    <input id="modal-edit-submit" type="submit" class="hidden">
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">@lang('admin/settings.oauth2.providers.modal.close')</button>
                    <button type="button" class="btn btn-primary" id="modal-edit-save" data-dismiss="modal">@lang('admin/settings.oauth2.providers.modal.save')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/admin/jquery.are-you-sure.js?t={cache-version}') !!}
    <script>
        $('form').areYouSure();
        $(document).ready(function () {
            $('#default-provider').val('{{ config('oauth2.default_driver') }}');
        });
        $(document).tooltip({
            selector: '[data-toggle="tooltip"]'
        });
        $(document).on('click', "[data-toggle='create']", function () {
            $('#oauth2-form').addClass('dirty');
            $('#modal-create').modal({
                show: true,
                backdrop: 'static'
            });
        });
        $('#modal-create-name').keypress(function (e) {
            if(! /[a-zA-Z0-9\-_]+/.test(String.fromCharCode(e.keyCode || e.which))) {
                return false;
            }
        }).change(function () {
            let b = false;
            for (let provider in  $('#saved-all-drivers').attr('value').split(',')) {
                if (provider === $(this).val()) {
                    b = true;
                    break;
                }
            }
            if (b) {
                this.setCustomValidity('@lang('admin/settings.oauth2.providers.modal.already_exists')');
            } else {
                this.setCustomValidity('');
            }
        });
        $('#modal-create-widget-html').keyup(function () {
            $('#modal-create-preview').html($(this).val());
        });
        $('#modal-create-widget-css').keyup(function () {
            $('#modal-widget-css').html($(this).val());
        });
        $("#modal-create-save").on('click', function () {
            $('#oauth2-form').addClass('dirty');
            if (!$('#modal-create-form')[0].checkValidity()) {
                $('#modal-create-submit').click();
                return;
            }
            let name = $('#modal-create-name').val().toLowerCase();
            let tr = "<tr id=\"provider-row-" + name + "\"><td>" + name.charAt(0).toUpperCase() + name.slice(1) +
                " <span id=\"default-notice-" + name + "\" class=\"hidden\" data-toggle=\"tooltip\" title=\"@lang('admin/settings.oauth2.providers.default_provider')\"><i class=\"fa fa-star\"></i></span>" +
                "<span id=\"unset-warning-" + name + "\" class=\"hidden\" data-toggle=\"tooltip\" title=\"@lang('admin/settings.oauth2.providers.unset_provider')\"><i class=\"fa fa-exclamation-triangle\"></i></span></td>" +
                "<td class=\"text-center\">" +
                "<span class=\"btn btn-xs btn-primary\" data-toggle=\"edit\" data-edit=\"" + name.charAt(0).toUpperCase() + name.slice(1) + "\">@lang('admin/settings.oauth2.providers.edit')</span> " +
                "<span id=\"button-delete-" + name + "\" class=\"btn btn-xs btn-danger\" data-toggle=\"delete\" data-delete=\"" + name.charAt(0).toUpperCase() + name.slice(1) + "\">@lang('admin/settings.oauth2.providers.delete')</span>" +
                "</td>" +
                "<td class=\"text-center\">" +
                "<input id=\"provider-status-value-" + name + "\" name=\"oauth2:providers:" + name + ":status\" class=\"hidden\" value=\"true\">" +
                "<span id=\"provider-state-on-" + name + "\" data-toggle=\"disable\" data-disable=\"" + name + "\" class=\"btn btn-xs text-green\"><i class=\"fa fa-check fa-2x\"></i></span>" +
                "<span id=\"provider-state-default-" + name + "\" class=\"btn btn-xs disabled text-green hidden\" data-toggle=\"tooltip\" title=\"@lang('admin/settings.oauth2.providers.default_provider_state_notice')\"><i class=\"fa fa-check fa-2x\"></i></span>" +
                "<span id=\"provider-state-off-" + name + "\" data-toggle=\"enable\" data-enable=\"" + name + "\" class=\"btn btn-xs text-red hidden\"><i class=\"fa fa-times fa-2x\"></i></span>" +
                "<span id=\"provider-state-unset-" + name + "\" class=\"btn btn-xs disabled text-red hidden\" data-toggle=\"tooltip\" title=\"@lang('admin/settings.oauth2.providers.unset_provider_state_notice')\"><i class=\"fa fa-times fa-2x\"></i></span>" +
                "</td></tr>";
            let saved = "<input id=\"saved-provider-id-" + name + "\" name=\"oauth2:providers:" + name + ":client_id\" value=\"" + $('#modal-create-id').val() + "\">" +
                "<input id=\"saved-provider-secret-" + name + "\" name=\"oauth2:providers:" + name + ":client_secret\" value=\"" + $('#modal-create-secret').val() + "\">" +
                "<input id=\"saved-provider-scopes-" + name + "\" name=\"oauth2:providers:" + name + ":scopes\" value=\"" + $('#modal-create-scopes').val() + "\">" +
                "<input id=\"saved-provider-widget-html-" + name + "\" name=\"oauth2:providers:" + name + ":widget_html\" value=\"" + encodeURI($('#modal-create-widget-html').val()) + "\">"  +
                "<input id=\"saved-provider-widget-css-" + name + "\" name=\"oauth2:providers:" + name + ":widget_css\" value=\"" + encodeURI($('#modal-create-widget-css').val()) + "\">"+
                "<input id=\"saved-provider-package-" + name + "\" name=\"oauth2:providers:" + name + ":package\" value=\"" + $('#modal-create-package').val() + "\">" +
                "<input id=\"saved-provider-listener-" + name + "\" name=\"oauth2:providers:" + name + ":listener\" value=\"" + $('#modal-create-listener').val() + "\">";
            $('#saved-deleted-providers').attr('value', $('#saved-deleted-providers').attr('value').replace(name, ''));
            $('#saved-new-providers').attr('value', $('#saved-new-providers').attr('value') + ($('#saved-new-providers').attr('value') === "" ? '' : ',') + name);
            $('#provider-table').append(tr);
            $('#saved-providers').append(saved);
            $('#default-provider').append("<option id=\"default-provider-option-" + name + "\" value=\"" + name + "\">" + name.charAt(0).toUpperCase() + name.slice(1) + "</option>");
            $('#modal-create-name').focus().val('');
            $('#modal-create-id').val('');
            $('#modal-create-secret').val('');
            $('#modal-create-scopes').val('');
            $('#modal-create-widget-html').val('');
            $('#modal-create-widget-css').val('');
            $('#modal-create-package').val('');
            $('#modal-create-listener').val('');
            $('#modal-create').modal('hide');
        });
        $(document).on('click', "[data-toggle='edit']", function () {
            $('#modal-edit-provider').val($(this).attr('data-edit').toLowerCase());
            $('#modal-edit-title').html('@lang('admin/settings.oauth2.providers.modal_edit_title')'.replace(':provider', '<code>' + $(this).attr('data-edit') + '</code>'));
            $('#modal-edit-listener').val($("#saved-provider-listener-" + $(this).attr('data-edit').toLowerCase()).val());
            $('#modal-edit-id').focus().val($("#saved-provider-id-" + $(this).attr('data-edit').toLowerCase()).val());
            $('#modal-edit-secret').val($("#saved-provider-secret-" + $(this).attr('data-edit').toLowerCase()).val());
            $('#modal-edit-scopes').val($("#saved-provider-scopes-" + $(this).attr('data-edit').toLowerCase()).val());
            $('#modal-edit-widget-html').val(unescape($("#saved-provider-widget-html-" + $(this).attr('data-edit').toLowerCase()).val()));
            $('#modal-edit-widget-css').val(unescape($("#saved-provider-widget-css-" + $(this).attr('data-edit').toLowerCase()).val()));
            $('#modal-widget-css').html(unescape($("#saved-provider-widget-css-" + $(this).attr('data-edit').toLowerCase()).val()));
            $('#modal-edit-preview').html(unescape($("#saved-provider-widget-html-" + $(this).attr('data-edit').toLowerCase()).val()));
            $('#modal-edit').modal({
                show: true,
                backdrop: 'static'
            });
        });
        $('#modal-edit-widget-html').keyup(function () {
            $('#modal-edit-preview').html($(this).val());
        });
        $('#modal-edit-widget-css').keyup(function () {
            $('#modal-widget-css').html($(this).val());
        });
        $(document).on('click', "#modal-edit-save", function () {
            $('#oauth2-form').addClass('dirty');
            if (!$('#modal-edit-form')[0].checkValidity()) {
                $('#modal-edit-submit').click();
                return;
            }
            let provider = $('#modal-edit-provider').val();
            $("#saved-provider-listener-" + provider).attr('value', $('#modal-edit-listener').val());
            $("#saved-provider-secret-" + provider).attr('value', $('#modal-edit-secret').val());
            $("#saved-provider-scopes-" + provider).attr('value', $('#modal-edit-scopes').val());
            $("#saved-provider-widget-html-" + provider).attr('value', encodeURI($('#modal-edit-widget-html').val()));
            $("#saved-provider-widget-css-" + provider).attr('value', encodeURI($('#modal-edit-widget-css').val()));
            let warning = $('#unset-warning-' + provider);
            let unset = $('#provider-state-unset-' + provider);
            let on = $('#provider-state-on-' + provider);
            let off = $('#provider-state-off-' + provider);
            if ($('#modal-edit-id').val() === '') {
                if ($('#default-provider').val() === provider) {
                    console.log('DEF AND EMPT');
                } else $("#saved-provider-id-" + provider).attr('value', $('#modal-edit-id').val());
            } else $("#saved-provider-id-" + provider).attr('value', $('#modal-edit-id').val());
            if($("#saved-provider-id-" + provider).attr('value') !== "") {
                warning.addClass('hidden');
                unset.addClass('hidden');
                if ($('#default-provider').val() !== provider) {
                    if (on.hasClass('hidden')) off.removeClass('hidden');
                }
                $("#default-provider-option-" + provider).removeAttr('hidden');
            } else {
                warning.removeClass('hidden');
                unset.removeClass('hidden');
                on.addClass('hidden');
                off.addClass('hidden');
                $('#provider-state-value-' + provider).attr('value', 'false');
                $("#default-provider-option-" + provider).attr('hidden', '');
            }
        });
        $(document).on('click', "[data-toggle='delete']", function () {
            $('#oauth2-form').addClass('dirty');
            let provider = $(this).attr('data-delete').toLowerCase();
            let that = this;
            swal({
                html: true,
                title: '@lang('admin/settings.oauth2.providers.delete_confirmation.title')'.replace(':provider', '<code>' + $(that).attr('data-delete') + '</code>'),
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                cancelButtonColor: '#d33',
                confirmButtonText: '@lang('admin/settings.oauth2.providers.delete_confirmation.confirm')',
                cancelButtonText: '@lang('admin/settings.oauth2.providers.delete_confirmation.cancel')'
            }, function () {
                $('#saved-deleted-providers').attr('value', $('#saved-deleted-providers').attr('value') + ($('#saved-deleted-providers').attr('value') === "" ? '' : ',') + provider);
                $("#saved-provider-id-" + provider).remove();
                $("#saved-provider-secret-" + provider).remove();
                $("#saved-provider-scopes-" + provider).remove();
                $("#saved-provider-widget-html-" + provider).remove();
                $("#saved-provider-widget-css-" + provider).remove();
                $("#saved-provider-package-" + provider).remove();
                $("#saved-provider-listener-" + provider).remove();
                $("#provider-row-" + provider).remove();
                $("default-provider-option-" + provider).remove();
            });
        });
        $(document).on('click', "[data-toggle='enable']", function () {
            $('#oauth2-form').addClass('dirty');
            let provider = $(this).attr('data-enable');
            $('#provider-state-on-' + provider).removeClass('hidden');
            $('#provider-state-off-' + provider).addClass('hidden');
            $('#provider-state-value-' + provider).attr('value', 'true');
        });
        $(document).on('click', "[data-toggle='disable']", function () {
            $('#oauth2-form').addClass('dirty');
            let provider = $(this).attr('data-disable');
            $('#provider-state-off-' + provider).removeClass('hidden');
            $('#provider-state-on-' + provider).addClass('hidden');
            $('#provider-state-value-' + provider).attr('value', 'false');
        });
        $(document).on('change', '#default-provider', function () {
            $('#oauth2-form').addClass('dirty');
            let def = $('#saved-default-provider').attr('value');
            let provider = $(this).val();
            $('#saved-default-provider').attr('value', provider);
            $('#button-delete-' + def).removeClass('hidden');
            $('#button-delete-' + provider).addClass('hidden');
            $('#default-notice-' + def).addClass('hidden');
            $('#provider-state-default-' + def).addClass('hidden');
            $('#provider-state-off-' + def).addClass('hidden');
            $('#provider-state-on-' + def).removeClass('hidden');
            $('#default-notice-' + provider).removeClass('hidden');
            $('#provider-state-default-' + provider).removeClass('hidden');
            $('#provider-state-off-' + provider).addClass('hidden');
            $('#provider-state-on-' + provider).addClass('hidden');
            $('#provider-state-value-' + provider).attr('value', 1);
        });
        $(document).on('click', "#save", function () {
            swal({
                html: true,
                title: '@lang('admin/settings.oauth2.save_notice.title')',
                text: '@lang('admin/settings.oauth2.save_notice.text')',
                type: 'info',
                button: false,
            });
        });
    </script>
@endsection
