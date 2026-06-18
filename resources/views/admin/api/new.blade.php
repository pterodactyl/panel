@extends('layouts.admin')

@section('title')
    Application API
@endsection

@section('content-header')
    <h1>Application API<small>Create a new application API key.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.api.index') }}">Application API</a></li>
        <li class="active">New Credentials</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="POST" action="{{ route('admin.api.new') }}">
            <div class="col-sm-8 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Select Permissions</h3>
                        <div class="box-tools">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-default" id="btn-bulk-read">Read All</button>
                                <button type="button" class="btn btn-sm btn-default" id="btn-bulk-rw">Read &amp; Write All</button>
                                <button type="button" class="btn btn-sm btn-default" id="btn-bulk-none">None All</button>
                            </div>
                        </div>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover" style="min-width: 650px;">
                            @foreach($resources as $resource)
                                <tr>
                                    <td class="strong" style="vertical-align: middle; padding-left: 15px;">
                                        {{ str_replace('_', ' ', title_case($resource)) }}
                                    </td>
                                    
                                    <td class="text-center" style="vertical-align: middle;">
                                        <div class="radio radio-primary" style="margin: 0;">
                                            <input type="radio" id="r_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['r'] }}">
                                            <label for="r_{{ $resource }}">Read</label>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center" style="vertical-align: middle;">
                                        <div class="radio radio-primary" style="margin: 0;">
                                            <input type="radio" id="rw_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['rw'] }}">
                                            <label for="rw_{{ $resource }}">Read &amp; Write</label>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center" style="vertical-align: middle;">
                                        <div class="radio" style="margin: 0;">
                                            <input type="radio" id="n_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['n'] }}" checked>
                                            <label for="n_{{ $resource }}">None</label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label" for="memoField">Description <span class="field-required"></span></label>
                            <input id="memoField" type="text" name="memo" class="form-control">
                        </div>
                        <p class="text-muted">Once you have assigned permissions and created this set of credentials you will be unable to come back and edit it. If you need to make changes down the road you will need to create a new set of credentials.</p>
                    </div>
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-success btn-sm pull-right">Create Credentials</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection


@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            
            function setButtonActive(activeButton) {
                $('#btn-bulk-read, #btn-bulk-rw, #btn-bulk-none')
                    .removeClass('btn-primary')
                    .addClass('btn-default');
                $(activeButton)
                    .removeClass('btn-default')
                    .addClass('btn-primary');
            }

            
            setButtonActive('#btn-bulk-none');

            $('#btn-bulk-read').click(function(e) {
                e.preventDefault();
                $('input[id^="r_"]').prop('checked', true);
                setButtonActive(this); 
            });

            $('#btn-bulk-rw').click(function(e) {
                e.preventDefault();
                $('input[id^="rw_"]').prop('checked', true);
                setButtonActive(this); 
            });

            $('#btn-bulk-none').click(function(e) {
                e.preventDefault();
                $('input[id^="n_"]').prop('checked', true);
                setButtonActive(this); 
            });
            
            
            $('input[type="radio"]').change(function() {
                $('#btn-bulk-read, #btn-bulk-rw, #btn-bulk-none')
                    .removeClass('btn-primary')
                    .addClass('btn-default');
            });
        });
    </script>
@endsection
