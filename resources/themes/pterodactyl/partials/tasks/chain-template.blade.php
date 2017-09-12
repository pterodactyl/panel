@section('tasks::chain-template')
<div class="box-footer with-border hidden" data-target="chain-clone">
    <div class="row">
        <div class="form-group col-md-3">
            <label class="control-label">@lang('server.tasks.new.chain_then'):</label>
            <div class="row">
                <div class="col-xs-4">
                    <select name="chain[time_value][]" class="form-control">
                        @foreach(range(1, 60) as $number)
                            <option value="{{ $number }}">{{ $number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xs-8">
                    <select name="chain[time_interval][]" class="form-control">
                        <option value="s">@lang('strings.seconds')</option>
                        <option value="m">@lang('strings.minutes')</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group col-md-3">
            <label class="control-label">@lang('server.tasks.new.chain_do'):</label>
            <div>
                <select name="chain[action][]" class="form-control">
                    <option value="command">@lang('server.tasks.actions.command')</option>
                    <option value="power">@lang('server.tasks.actions.power')</option>
                </select>
            </div>
        </div>
        <div class="form-group col-md-6">
            <label class="control-label">@lang('server.tasks.new.chain_arguments'):</label>
            <div class="input-group">
                <input type="text" name="chain[payload][]" class="form-control">
                <div class="input-group-btn">
                    <button type="button" class="btn btn-danger" data-action="remove-chain-element"><i class="fa fa-close"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
@show
