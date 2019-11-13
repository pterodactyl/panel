@section('tasks::chain-template')
<div class="box-footer with-border task-list-item" data-target="task-clone">
    <div class="row">
        <div class="form-group col-md-3">
            <label class="control-label">@lang('server.schedule.task.time')</label>
            <div class="row">
                <div class="col-xs-4">
                    <select name="tasks[time_value][]" class="form-control">
                        @foreach(range(0, 59) as $number)
                            <option value="{{ $number }}">{{ $number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xs-8">
                    <select name="tasks[time_interval][]" class="form-control">
                        <option value="s">@lang('strings.seconds')</option>
                        <option value="m">@lang('strings.minutes')</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group col-md-3">
            <label class="control-label">@lang('server.schedule.task.action')</label>
            <div>
                <select name="tasks[action][]" class="form-control">
                    <option value="command">@lang('server.schedule.actions.command')</option>
                    <option value="power">@lang('server.schedule.actions.power')</option>
                </select>
            </div>
        </div>
        <div class="form-group col-md-6">
        <label class="control-label">@lang('server.schedule.task.payload')</label>
      <div data-attribute="remove-task-element">
        <input type="text" name="tasks[payload][]" class="form-control" id="cmdtextbox">
        <label class="control-label">@lang('server.schedule.task.actions')</label>
        <select class="form-control" id="actions" onchange="func()">
          <option value="">Select</option>
          <option value="stop">Stop</option>
          <option value="start">Start</option>
          <option value="restart">Restart</option>
          <option value="kill">Kill</option>
        </select>
      </div>
      <script>
        function func() {
          var dropdown = document.getElementById("actions");
          var selection = dropdown.value;
          console.log(selection);
          var cmdtextbox = document.getElementById("cmdtextbox");
          cmdtextbox.value = selection;
        }</script>
                <div class="input-group-btn hidden">
                    <button type="button" class="btn btn-danger" data-action="remove-task"><i class="fa fa-close"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
@show
