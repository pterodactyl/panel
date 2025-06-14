@php
    $router = app('router');
@endphp
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="{{ $router->currentRouteNamed('admin.shop.settings.payments') ? 'active' : '' }}">
                    <a href="{{ route('admin.shop.settings.payments') }}">Payments</a></li>
                <li class="{{ $router->currentRouteNamed('admin.shop.settings.servers') ? 'active' : '' }}">
                    <a href="{{ route('admin.shop.settings.servers') }}">Server Settings</a></li>
                <li class="{{ $router->currentRouteNamed('admin.shop.settings.tos') ? 'active' : '' }}">
                    <a href="{{ route('admin.shop.settings.tos') }}">Terms Of Services</a></li>
                <li class="{{ $router->currentRouteNamed('admin.shop.settings.invoice') ? 'active' : '' }}">
                    <a href="{{ route('admin.shop.settings.invoice') }}">Invoice Settings</a></li>
            </ul>
        </div>
    </div>
</div>
