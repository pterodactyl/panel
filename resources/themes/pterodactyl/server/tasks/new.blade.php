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
    <h1>@lang('server.schedule.new.header')<small>@lang('server.schedule.new.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li><a href="{{ route('server.tasks', $server->uuidShort) }}">@lang('navigation.server.task_management')</a></li>
        <li class="active">@lang('server.schedule.new.header')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('server.tasks.new', $server->uuidShort) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('server.schedule.setup')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-xs-12">
                            <label class="control-label" for="scheduleName">@lang('strings.name') <span class="field-optional"></span></label>
                            <div>
                                <input type="text" name="name" id="scheduleName" class="form-control" value="{{ old('name') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6 col-md-3">
                            <div class="form-group">
                                <label for="scheduleDayOfWeek" class="control-label">@lang('server.schedule.day_of_week')</label>
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
                            <div class="form-group">
                                <input type="text" id="scheduleDayOfWeek" class="form-control" name="day_of_week" value="{{ old('day_of_week') }}" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <div class="form-group">
                                <label for="scheduleDayOfMonth" class="control-label">@lang('server.schedule.day_of_month')</label>
                                <div>
                                    <select data-action="update-field" data-field="day_of_month" class="form-control" multiple>
                                        @foreach(range(1, 31) as $i)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="text" id="scheduleDayOfMonth" class="form-control" name="day_of_month" value="{{ old('day_of_month') }}" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <div class="form-group col-md-12">
                                <label for="scheduleHour" class="control-label">@lang('server.schedule.hour')</label>
                                <div>
                                    <select data-action="update-field" data-field="hour" class="form-control" multiple>
                                        @foreach(range(0, 23) as $i)
                                            <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <input type="text" id="scheduleHour" class="form-control" name="hour" value="{{ old('hour') }}" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <div class="form-group">
                                <label for="scheduleMinute" class="control-label">@lang('server.schedule.minute')</label>
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
                            <div class="form-group">
                                <input type="text" id="scheduleMinute" class="form-control" name="minute" value="{{ old('minute') }}" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer with-border">
                    <p class="small text-muted no-margin-bottom">@lang('server.schedule.time_help')</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary" id="containsTaskList">
                @include('partials.schedules.task-template')
                <div class="box-footer with-border" id="taskAppendBefore">
                    <div class="pull-left">
                        <p class="text-muted small">@lang('server.schedule.task_help')</p>
                    </div>
                    <div class="pull-right">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-sm btn-default" data-action="add-new-task"><i class="fa fa-plus"></i> @lang('server.schedule.task.add_more')</button>
                        <button type="submit" class="btn btn-sm btn-success">@lang('server.schedule.new.submit')</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    {!! Theme::js('vendor/select2/select2.full.min.js') !!}
    {!! Theme::js('js/frontend/tasks/view-actions.js') !!}
@endsection
