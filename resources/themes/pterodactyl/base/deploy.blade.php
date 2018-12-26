@extends('layouts.master')

@section('title')
    @lang('base.index.header')
@endsection

@section('content-header')
    <h1>@lang('base.deploy.header')<small>@lang('base.deploy.header_sub')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li class="active">@lang('strings.deploy')</li>
    </ol>
@endsection

@section('content')
    <form method="POST" action="{{ route('deploy.submit') }}">
        <div class="row">
            <div class="col-xs-12 col-sm-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.billing.deploy.select_nest')</h3>
                    </div>
                    <div class="box-body">
                        @foreach($nests as $nest)
                            <label><input name="nest" type="radio" value="{{ $nest->id }}"> {{ $nest->name }}</label><br />
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('base.billing.deploy.hardware.heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="control-label">@lang('base.billing.deploy.hardware.name')</label>
                                <div>
                                    <input class="form-control" type="text" name="name">
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label">@lang('base.billing.deploy.hardware.ram')</label>
                                <div>
                                    <input type="number" name="ram" value="1024" step="1" min="256" max="4096" class="form-control">
                                    <p class="text-muted small no-margin">@lang('base.billing.deploy.hardware.ram_description')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label">@lang('base.billing.deploy.hardware.disk')</label>
                                <div>
                                    <input type="number" name="disk" value="5" step="1" min="1" max="25" class="form-control">
                                    <p class="text-muted small no-margin">@lang('base.billing.deploy.hardware.disk_description')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @foreach($nests as $nest)
            <div class="box" data-nest="{{ $nest->id }}" data-memory-cost="{{ $nest->memory_monthly_cost }}" data-disk-cost="{{ $nest->disk_monthly_cost }}" data-memory-max="{{ $nest->max_memory }}" data-disk-max="{{ $nest->max_disk }}" style="display: none;">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $nest->name }}</h3>
                </div>
                <div class="box-body">
                    <p>{{ $nest->description }}</p>
                    @foreach ($nest->eggs as $egg)
                        <label><input name="egg" type="radio" value="{{ $egg->id }}"> {{ $egg->name }}</label><br />
                    @endforeach
                </div>
            </div>
            @foreach ($nest->eggs as $egg)
                <div class="box" data-egg="{{ $egg->id }}" style="display: none;">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $egg->name }}</h3>
                    </div>
                    <div class="box-body">
                        <p>{{ $egg->description }}</p>
                        @foreach ($egg->variables()->where('user_editable', 1)->get() as $var)
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="control-label">{{ $var->name }}</label>
                                    <div>
                                        <input type="text" name="v{{$nest->id}}-{{$egg->id}}-{{$var->env_variable}}" value="{{ $var->default_value }}" class="form-control">
                                        @if ($var->description)
                                            <p class="text-muted small no-margin">{{ $var->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endforeach

        <div class="box">
            <div class="box-footer">
                {!! csrf_field() !!}
                <input type="submit" class="btn btn-primary btn-sm" value="@lang('base.billing.deploy.start')" />
                <span id="price">$0.00/month</span>
            </div>
        </div>
    </form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        var memory_cost, disk_cost, memory_max, disk_max;
        var $ram = $("[name='ram']"), $disk = $("[name='disk']");
        $('[name="nest"]').on('change', function() {
            var id = $('[name="nest"]:checked').val();
            var $nest = $("[data-nest='"+id+"']");
            $("[data-nest]").hide();
            $("[data-egg]").hide();
            memory_cost = $nest.data('memory-cost');
            disk_cost = $nest.data('disk-cost');
            memory_max = $nest.data('memory-max');
            disk_max = $nest.data('disk-max');
            if ($ram.val()/1024 > memory_max) $ram.val(memory_max*1024);
            if ($disk.val() > disk_max) $disk.val(disk_max);
            $ram.attr('max', memory_max*1024);
            $disk.attr('max', disk_max);
            update_price();
            $nest.show();
        });
        $('[name="egg"]').on('change', function() {
            var id = $('[name="egg"]:checked').val();
            $("[data-egg]").hide();
            $("[data-egg='"+id+"']").show();
        });
        function update_price() {
            var cost = 0;
            cost += ($ram.val()/1024)*memory_cost;
            cost += $disk.val()*disk_cost;
            $("#price").text("$"+cost.toFixed(2)+"/month");
        }
        $ram.on('change', update_price);
        $disk.on('change', update_price);
    </script>
@endsection
