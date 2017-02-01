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
@extends('layouts.master')

@section('title')
    @lang('server.tasks.new.header')
@endsection

@section('scripts')
    {{-- This has to be loaded before the AdminLTE theme to avoid dropdown issues. --}}
    {!! Theme::css('vendor/select2/select2.min.css') !!}
    @parent
@endsection

@section('content-header')
    <h1>@lang('server.tasks.new.header')<small>@lang('server.tasks.new.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li><a href="{{ route('server.tasks', $server->uuidShort) }}">@lang('navigation.server.task_management')</a></li>
        <li class="active">@lang('server.tasks.new_task')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('server.tasks.new', $server->uuidShort) }}" method="POST">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.tasks.new.day_of_week')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <div>
                                <select data-action="update-field" data-field="day_of_week" class="form-control" multiple>
                                    <option value="0">@lang('server.tasks.new.sun')</option>
                                    <option value="1">@lang('server.tasks.new.mon')</option>
                                    <option value="2">@lang('server.tasks.new.tues')</option>
                                    <option value="3">@lang('server.tasks.new.wed')</option>
                                    <option value="4">@lang('server.tasks.new.thurs')</option>
                                    <option value="5">@lang('server.tasks.new.fri')</option>
                                    <option value="6">@lang('server.tasks.new.sat')</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">@lang('server.tasks.new.custom')</label>
                            <div>
                                <input type="text" class="form-control" name="day_of_week" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.tasks.new.day_of_month')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <div>
                                <select data-action="update-field" data-field="day_of_month" class="form-control" multiple>
                                    @foreach(range(1, 31) as $i)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">@lang('server.tasks.new.custom')</label>
                            <div>
                                <input type="text" class="form-control" name="day_of_month" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.tasks.new.hour')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <div>
                                <select data-action="update-field" data-field="hour" class="form-control" multiple>
                                    @foreach(range(0, 23) as $i)
                                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">@lang('server.tasks.new.custom')</label>
                            <div>
                                <input type="text" class="form-control" name="hour" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.tasks.new.minute')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <div>
                                <select data-action="update-field" data-field="minute" class="form-control" multiple>
                                    @foreach(range(0, 55) as $i)
                                        @if($i % 5 === 0)
                                            <option value="{{ $i }}">_ _:{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">@lang('server.tasks.new.custom')</label>
                            <div>
                                <input type="text" class="form-control" name="minute" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="control-label">@lang('server.tasks.new.type'):</label>
                            <div>
                                <select name="action" class="form-control">
                                    <option value="command">@lang('server.tasks.actions.command')</option>
                                    <option value="power">@lang('server.tasks.actions.power')</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-8">
                            <label class="control-label">@lang('server.tasks.new.payload'):</label>
                            <div>
                                <input type="text" name="data" class="form-control" value="{{ old('data') }}">
                                <span class="text-muted small">@lang('server.tasks.new.payload_help')</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer with-border">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-sm btn-success">@lang('server.tasks.new.submit')</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    {!! Theme::js('vendor/select2/select2.full.min.js') !!}
    <script>
    $('select[name="action"]').select2();
    $('[data-action="update-field"]').on('change', function (event) {
        event.preventDefault();
        var updateField = $(this).data('field');
        var selected = $(this).map(function (i, opt) {
            return $(opt).val();
        }).toArray();
        if (selected.length === $(this).find('option').length) {
            $('input[name=' + updateField + ']').val('*');
        } else {
            $('input[name=' + updateField + ']').val(selected.join(','));
        }
    });
    </script>
@endsection
