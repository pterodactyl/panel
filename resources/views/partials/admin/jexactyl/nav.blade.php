@section('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li @if($activeTab === 'index')class="active"@endif><a href="{{ route('admin.index') }}">Home</a></li>
                    <li @if($activeTab === 'theme')class="active"@endif><a href="{{ route('admin.jexactyl.theme') }}">Appearance</a></li>
                    <li @if($activeTab === 'mail')class="active"@endif><a href="{{ route('admin.jexactyl.mail') }}">Mail</a></li>
                    <li @if($activeTab === 'advanced')class="active"@endif><a href="{{ route('admin.jexactyl.advanced') }}">Advanced</a></li>
                    <li style="margin-left: 5px; margin-right: 5px;"><a>-</a></li>
                    <li @if($activeTab === 'store')class="active"@endif><a href="{{ route('admin.jexactyl.store') }}">Storefront</a></li>
                    <li @if($activeTab === 'registration')class="active"@endif><a href="{{ route('admin.jexactyl.registration') }}">Registration</a></li>
                    <li @if($activeTab === 'approvals')class="active"@endif><a href="{{ route('admin.jexactyl.approvals') }}">Approvals</a></li>
                    <li @if($activeTab === 'server')class="active"@endif><a href="{{ route('admin.jexactyl.server') }}">Server Settings</a></li>
                    <li @if($activeTab === 'referrals')class="active"@endif><a href="{{ route('admin.jexactyl.referrals') }}">Referrals</a></li>
                    
                </ul>
            </div>
        </div>
    </div>
@endsection
