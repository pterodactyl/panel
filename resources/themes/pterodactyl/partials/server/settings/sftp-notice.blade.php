@section('sftp::notice')
    @if(config('oauth2.enabled') && !empty(Auth::user()->oauth2_id))
        <div class="row">
            <div class="col-xs-12">
                <div class="alert alert-danger">
                    {!! __('server.config.sftp.oauth2_notice') !!}
                </div>
            </div>
        </div>
    @endif
@endsection
