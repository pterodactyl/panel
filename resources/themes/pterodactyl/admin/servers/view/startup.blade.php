{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Startup
@endsection

@section('content-header')
    <h1>{{ $server->name }}<small>Control startup command as well as variables.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.servers') }}">Servers</a></li>
        <li><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
        <li class="active">Startup</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.servers.view', $server->id) }}">About</a></li>
                @if($server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">Details</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">Build Configuration</a></li>
                    <li class="active"><a href="{{ route('admin.servers.view.startup', $server->id) }}">Startup</a></li>
                    <li><a href="{{ route('admin.servers.view.database', $server->id) }}">Database</a></li>
                @endif
                <li><a href="{{ route('admin.servers.view.manage', $server->id) }}">Manage</a></li>
                <li class="tab-danger"><a href="{{ route('admin.servers.view.delete', $server->id) }}">Delete</a></li>
                <li class="tab-success"><a href="{{ route('server.index', $server->uuidShort) }}"><i class="fa fa-external-link"></i></a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.servers.view.startup', $server->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Startup Command Modification</h3>
                </div>
                <div class="box-body">
                    <label for="pStartup" class="form-label">Startup Command</label>
                    <input id="pStartup" name="startup" class="form-control" type="text" value="{{ old('startup', $server->startup) }}" />
                    <p class="small text-muted">Edit your server's startup command here. The following variables are available by default: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, and <code>@{{SERVER_PORT}}</code>.</p>
                </div>
                <div class="box-body">
                    <label for="pDefaultStartupCommand" class="form-label">Default Service Start Command</label>
                    <input id="pDefaultStartupCommand" class="form-control" type="text" readonly />
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary btn-sm pull-right">Save Modifications</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Service Configuration</h3>
                </div>
                <div class="box-body row">
                    <div class="col-xs-12">
                        <p class="small text-danger">
                            Changing any of the below values will result in the server processing a re-install command. The server will be stopped and will then proceede.
                            If you are changing the pack, exisiting data <em>may</em> be overwritten. If you would like the service scripts to not run, ensure the box is checked at the bottom.
                        </p>
                        <p class="small text-danger">
                            <strong>This is a destructive operation in many cases. This server will be stopped immediately in order for this action to proceede.</strong>
                        </p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pServiceId">Service</label>
                        <select name="service_id" id="pServiceId" class="form-control">
                            @foreach($services as $service)
                                <option value="{{ $service->id }}"
                                    @if($service->id === $server->service_id)
                                        selected="selected"
                                    @endif
                                >{{ $service->name }}</option>
                            @endforeach
                        </select>
                        <p class="small text-muted no-margin">Select the type of service that this server will be running.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pOptionId">Option</label>
                        <select name="option_id" id="pOptionId" class="form-control"></select>
                        <p class="small text-muted no-margin">Select the type of sub-service that this server will be running.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="pPackId">Service Pack</label>
                        <select name="pack_id" id="pPackId" class="form-control"></select>
                        <p class="small text-muted no-margin">Select a service pack to be automatically installed on this server when first created.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pSkipScripting" name="skip_scripting" type="checkbox" value="1" @if($server->skip_scripts) checked @endif />
                            <label for="pSkipScripting" class="strong">Skip Service Option Install Script</label>
                        </div>
                        <p class="small text-muted no-margin">If the selected <code>Option</code> has an install script attached to it, the script will run during install after the pack is installed. If you would like to skip this step, check this box.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row" id="appendVariablesTo">
            @foreach($server->option->variables as $variable)
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ $variable->name }}</h3>
                        </div>
                        <div class="box-body">
                            <input data-action="match-regex" name="env_{{ $variable->id }}" class="form-control" type="text" value="{{ old('env_' . $variable->id, $variable->server_value) }}" />
                            <p class="no-margin small text-muted">{{ $variable->description }}</p>
                            <p class="no-margin">
                                @if($variable->required)<span class="label label-danger">Required</span>@else<span class="label label-default">Optional</span>@endif
                                @if($variable->user_viewable)<span class="label label-success">Visible</span>@else<span class="label label-primary">Hidden</span>@endif
                                @if($variable->user_editable)<span class="label label-success">Editable</span>@else<span class="label label-primary">Locked</span>@endif
                            </p>
                        </div>
                        <div class="box-footer">
                            <p class="no-margin text-muted small"><strong>Startup Command Variable:</strong> <code>{{ $variable->env_variable }}</code></p>
                            <p class="no-margin text-muted small"><strong>Input Rules:</strong> <code>{{ $variable->rules }}</code></p>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    <script>
    $(document).ready(function () {
        $('#pServiceId').select2({
            placeholder: 'Select a Service',
        }).change();
        $('#pOptionId').select2({
            placeholder: 'Select a Service Option',
        });
        $('#pPackId').select2({
            placeholder: 'Select a Service Pack',
        });

        $('input[data-action="match-regex"]').on('keyup', function (event) {
            if (! $(this).data('regex')) return;

            var input = $(this).val();
            var regex = new RegExp($(this).data('regex').replace(/^\/|\/$/g, ''));

            $(this).parent().parent().removeClass('has-success has-error').addClass((! regex.test(input)) ? 'has-error' : 'has-success');
        });
    });
    </script>
    <script>
        $('#pServiceId').on('change', function (event) {
            $('#pOptionId').html('').select2({
                data: $.map(_.get(Pterodactyl.services, $(this).val() + '.options', []), function (item) {
                    console.log(item);
                    return {
                        id: item.id,
                        text: item.name,
                    };
                }),
            }).val('{{ $server->option_id }}').change();
        });

        $('#pOptionId').on('change', function (event) {
            var parentChain = _.get(Pterodactyl.services, $('#pServiceId').val(), null);
            var objectChain = _.get(parentChain, 'options.' + $(this).val(), null);

            $('#pDefaultContainer').val(_.get(objectChain, 'docker_image', 'not defined!'));

            if (!_.get(objectChain, 'startup', false)) {
                $('#pDefaultStartupCommand').val(_.get(parentChain, 'startup', 'ERROR: Startup Not Defined!'));
            } else {
                $('#pDefaultStartupCommand').val(_.get(objectChain, 'startup'));
            }

            $('#pPackId').html('').select2({
                data: [{ id: 0, text: 'No Service Pack' }].concat(
                    $.map(_.get(objectChain, 'packs', []), function (item, i) {
                        return {
                            id: item.id,
                            text: item.name + ' (' + item.version + ')',
                        };
                    })
                ),
            }).val('{{ is_null($server->pack_id) ? 0 : $server->pack_id }}');

            $('#appendVariablesTo').html('');
            $.each(_.get(objectChain, 'variables', []), function (i, item) {
                var setValue = _.get(Pterodactyl.server_variables, 'env_' + item.id + '.value', item.default_value);
                var isRequired = (item.required === 1) ? '<span class="label label-danger">Required</span> ' : '';
                var dataAppend = ' \
                    <div class="col-xs-12"> \
                        <div class="box"> \
                            <div class="box-header with-border"> \
                                <h3 class="box-title">' + isRequired + item.name + '</h3> \
                            </div> \
                            <div class="box-body"> \
                                <input data-action="match-regex" name="env_' + item.id + '" class="form-control" type="text" value="' + setValue + '" /> \
                                <p class="no-margin small text-muted">' + item.description + '</p> \
                            </div> \
                            <div class="box-footer"> \
                                <p class="no-margin text-muted small"><strong>Startup Command Variable:</strong> <code>' + item.env_variable + '</code></p> \
                                <p class="no-margin text-muted small"><strong>Input Rules:</strong> <code>' + item.rules + '</code></p> \
                            </div> \
                        </div> \
                    </div>';
                $('#appendVariablesTo').append(dataAppend);
            });
        });
    </script>
@endsection
