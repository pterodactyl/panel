<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Debugging</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="form-group col-md-4">
                <label class="control-label">Debug Mode</label>
                <div>
                    <select class="form-control" name="debug_mode">
                        <option value="false">Disabled</option>
                        <option value="true" @if(config('app.debug')) selected @endif>Enabled</option>
                    </select>
                    <p class="text-muted small">When enabled, the panel shows detailed error pages with stack traces and environment information instead of a generic error message. Keep this disabled in production, as debug output can expose sensitive information.</p>
                </div>
            </div>
        </div>
    </div>
</div>
