@section('sftp::notice')
    @if(! (env('OAUTH2')) && isset(Auth::user()->getAttributes()['oauth2_id']))
        <div class="row">
            <div class="col-xs-12">
                <div class="alert alert-danger">
                    {!! __('server.config.sftp.oauth2_notice') !!}
                </div>
            </div>
        </div>
    @endif
@endsection
