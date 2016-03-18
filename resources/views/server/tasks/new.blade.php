{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

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
    Scheduled Tasks
@endsection

@section('content')
<div class="col-md-12">
    <h3 class="nopad">Create Scheduled Task<br /><small>Current System Time: {{ Carbon::now()->toDayDateTimeString() }}</small></h3><hr />
    <form action="{{ route('server.tasks.new', $server->uuidShort) }}" method="POST">
        <div class="alert alert-info">You may use either the dropdown selection boxes or enter custom <code>cron</code> variables into the fields below.</div>
        <div class="row">
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="control-label">Day of Week:</label>
                            <div>
                                <select data-action="update-field" data-field="day_of_week" class="form-control" multiple>
                                    <option value="0">Sunday</option>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">Custom Value:</label>
                            <div>
                                <input type="text" class="form-control" name="day_of_week" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="control-label">Day of Month:</label>
                            <div>
                                <select data-action="update-field" data-field="day_of_month" class="form-control" multiple>
                                    @foreach(range(1, 31) as $i)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">Custom Value:</label>
                            <div>
                                <input type="text" class="form-control" name="day_of_month" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="control-label">Hour:</label>
                            <div>
                                <select data-action="update-field" data-field="hour" class="form-control" multiple>
                                    @foreach(range(0, 23) as $i)
                                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">Custom Value:</label>
                            <div>
                                <input type="text" class="form-control" name="hour" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="well">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="control-label">Minute:</label>
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
                            <label class="control-label">Custom Value:</label>
                            <div>
                                <input type="text" class="form-control" name="minute" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4">
                <label class="control-label">Task Type:</label>
                <div>
                    <select name="action" class="form-control">
                        <option value="command">Send Command</option>
                        <option value="power">Send Power Action</option>
                    </select>
                </div>
            </div>
            <div class="form-group col-md-8">
                <label class="control-label">Task Payload:</label>
                <div>
                    <input type="text" name="data" class="form-control" value="{{ old('data') }}">
                    <p class="text-muted"><small>For example, if you selected <code>Send Command</code> enter the command here. If you selected <code>Send Power Option</code> put the power action here (e.g. <code>restart</code>).</small></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                {!! csrf_field() !!}
                <input type="submit" class="btn btn-success btn-sm" value="Schedule Task" />
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function () {
    $('.server-tasks').addClass('active');
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
});
</script>
@endsection
