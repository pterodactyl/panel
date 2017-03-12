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
                @if(! $server->trashed() && $server->installed === 1)
                    <li><a href="{{ route('admin.servers.view.details', $server->id) }}">Details</a></li>
                    <li><a href="{{ route('admin.servers.view.build', $server->id) }}">Build Configuration</a></li>
                    <li class="active"><a href="{{ route('admin.servers.view.startup', $server->id) }}">Startup</a></li>
                    <li><a href="{{ route('admin.servers.view.database', $server->id) }}">Database</a></li>
                @endif
                @if(! $server->trashed())
                    <li><a href="{{ route('admin.servers.view.manage', $server->id) }}">Manage</a></li>
                @endif
                <li class="tab-danger"><a href="{{ route('admin.servers.view.delete', $server->id) }}">Delete</a></li>
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
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary btn-sm pull-right">Save Modifications</button>
                </div>
            </div>
        </div>
        @foreach($server->option->variables as $variable)
            <div class="col-xs-12 col-md-4 col-sm-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $variable->name }}</h3>
                    </div>
                    <div class="box-body">
                        <input data-action="match-regex" data-regex="{{ $variable->regex }}" name="env_{{ $variable->id }}" class="form-control" type="text" value="{{ old('env_' . $variable->id, $variable->server_value) }}" />
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
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('input[data-action="match-regex"]').on('keyup', function (event) {
        if (! $(this).data('regex')) return;

        var input = $(this).val();
        var regex = new RegExp($(this).data('regex').replace(/^\/|\/$/g, ''));

        $(this).parent().parent().removeClass('has-success has-error').addClass((! regex.test(input)) ? 'has-error' : 'has-success');
    });
    </script>
@endsection
