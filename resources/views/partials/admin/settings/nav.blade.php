@include('partials/admin.settings.notice')

@section('settings::nav')
    @yield('settings::notice')
    <div class="row">
        <div class="col-xs-12">
            <div class="nav-tabs-custom nav-tabs-floating">
                <ul class="nav nav-tabs">
                    <li @class(['active' => $activeTab === 'basic'])><a href="{{ route('admin.settings') }}">General</a></li>
                    <li @class(['active' => $activeTab === 'mail'])><a href="{{ route('admin.settings.mail') }}">Mail</a></li>
                    <li @class(['active' => $activeTab === 'advanced'])><a href="{{ route('admin.settings.advanced') }}">Advanced</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
