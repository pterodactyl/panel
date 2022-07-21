@section('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li @if($activeTab === 'index')class="active"@endif><a href="{{ route('admin.jexactyl.index') }}">Home</a></li>
                    <li @if($activeTab === 'store')class="active"@endif><a href="{{ route('admin.jexactyl.store') }}">Storefront</a></li>
                    <li @if($activeTab === 'registration')class="active"@endif><a href="{{ route('admin.jexactyl.registration') }}">User Registration</a></li>
                    <li @if($activeTab === 'renewal')class="active"@endif><a href="{{ route('admin.jexactyl.renewal') }}">Server Renewal</a></li>
                    <li @if($activeTab === 'referrals')class="active"@endif><a href="{{ route('admin.jexactyl.referrals') }}">Referrals</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
