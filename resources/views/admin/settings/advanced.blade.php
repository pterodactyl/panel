@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'advanced'])

@section('title')
    Advanced Settings
@endsection

@section('content-header')
    <h1>Advanced Settings<small>Configure advanced settings for Pterodactyl.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="" method="POST">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">reCAPTCHA</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Status</label>
                                <div>
                                    <select class="form-control" name="recaptcha:enabled">
                                        <option value="true">Enabled</option>
                                        <option value="false" @if(old('recaptcha:enabled', config('recaptcha.enabled')) == '0') selected @endif>Disabled</option>
                                    </select>
                                    <p class="text-muted small">If enabled, login forms and password reset forms will do a silent captcha check and display a visible captcha if needed.</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Site Key</label>
                                <div>
                                    <input type="text" required class="form-control" name="recaptcha:website_key" value="{{ old('recaptcha:website_key', config('recaptcha.website_key')) }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Secret Key</label>
                                <div>
                                    <input type="text" required class="form-control" name="recaptcha:secret_key" value="{{ old('recaptcha:secret_key', config('recaptcha.secret_key')) }}">
                                    <p class="text-muted small">Used for communication between your site and Google. Be sure to keep it a secret.</p>
                                </div>
                            </div>
                        </div>
                        @if($showRecaptchaWarning)
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="alert alert-warning no-margin">
                                        You are currently using reCAPTCHA keys that were shipped with this Panel. For improved security it is recommended to <a href="https://www.google.com/recaptcha/admin">generate new invisible reCAPTCHA keys</a> that are tied specifically to your website.
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">HTTP Connections</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">Connection Timeout</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:guzzle:connect_timeout" value="{{ old('pterodactyl:guzzle:connect_timeout', config('pterodactyl.guzzle.connect_timeout')) }}">
                                    <p class="text-muted small">The amount of time in seconds to wait for a connection to be opened before throwing an error.</p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Request Timeout</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:guzzle:timeout" value="{{ old('pterodactyl:guzzle:timeout', config('pterodactyl.guzzle.timeout')) }}">
                                    <p class="text-muted small">The amount of time in seconds to wait for a request to be completed before throwing an error.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Automatic Allocation Creation</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Status</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:client_features:allocations:enabled">
                                        <option value="false">Disabled</option>
                                        <option value="true" @if(old('pterodactyl:client_features:allocations:enabled', config('pterodactyl.client_features.allocations.enabled'))) selected @endif>Enabled</option>
                                    </select>
                                    <p class="text-muted small">If enabled users will have the option to automatically create new allocations for their server via the frontend.</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Starting Port</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:client_features:allocations:range_start" value="{{ old('pterodactyl:client_features:allocations:range_start', config('pterodactyl.client_features.allocations.range_start')) }}">
                                    <p class="text-muted small">The starting port in the range that can be automatically allocated.</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Ending Port</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:client_features:allocations:range_end" value="{{ old('pterodactyl:client_features:allocations:range_end', config('pterodactyl.client_features.allocations.range_end')) }}">
                                    <p class="text-muted small">The ending port in the range that can be automatically allocated.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Trusted Proxies</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label" for="trusted-proxies">Proxy IP Addresses</label>

                            <input
                                type="hidden"
                                id="trusted-proxies-value"
                                name="trustedproxy:proxies"
                                value="{{ old('trustedproxy:proxies', $trustedProxies) }}"
                            >

                            <select id="trusted-proxies" class="form-control" multiple>
                                @foreach(array_filter(explode(',', old('trustedproxy:proxies', $trustedProxies) ?? '')) as $proxy)
                                    <option value="{{ trim($proxy) }}" selected>{{ trim($proxy) }}</option>
                                @endforeach
                            </select>

                            <div style="margin-top: 10px;">
                                <button type="button" id="set-cloudflare-proxies" class="btn btn-sm btn-default">
                                    Set to Cloudflare IPs
                                </button>
                                <button type="button" id="clear-trusted-proxies" class="btn btn-sm btn-default">
                                    Clear
                                </button>
                            </div>

                            <p class="text-muted small">Type an IPv4 or IPv6 address or CIDR range and press Enter to add it. Click the × to remove an entry. Leave empty to trust no proxies, or enter * alone to trust all proxies. Click Save to apply changes.</p>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Debug Mode</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="app-debug" class="control-label">Status</label>
                            <select id="app-debug" name="app:debug" class="form-control">
                                <option value="false" @if(old('app:debug', config('app.debug') ? 'true' : 'false') === 'false') selected @endif>Disabled</option>
                                <option value="true" @if(old('app:debug', config('app.debug') ? 'true' : 'false') === 'true') selected @endif>Enabled</option>
                            </select>
                            <p class="text-muted small">Show detailed Laravel error pages when enabled. Keep disabled in production because error pages can expose sensitive configuration and stack traces. Click Save to apply changes.</p>
                        </div>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(function () {
            var trustedProxies = $('#trusted-proxies');

            trustedProxies.select2({
                tags: true,
                selectOnClose: true,
                tokenSeparators: [',', ' '],
            });

            function syncTrustedProxies() {
                $('#trusted-proxies-value').val(
                    (trustedProxies.val() || []).join(',')
                );
            }

// These Cloudflare IPV4/6's should be up to date. They will be updated if not.
            $('#set-cloudflare-proxies').on('click', function () {
                var cloudflareProxies = [
                    '173.245.48.0/20',
                    '103.21.244.0/22',
                    '103.22.200.0/22',
                    '103.31.4.0/22',
                    '141.101.64.0/18',
                    '108.162.192.0/18',
                    '190.93.240.0/20',
                    '188.114.96.0/20',
                    '197.234.240.0/22',
                    '198.41.128.0/17',
                    '162.158.0.0/15',
                    '104.16.0.0/13',
                    '104.24.0.0/14',
                    '172.64.0.0/13',
                    '131.0.72.0/22',
                    '2400:cb00::/32',
                    '2606:4700::/32',
                    '2803:f800::/32',
                    '2405:b500::/32',
                    '2405:8100::/32',
                    '2a06:98c0::/29',
                    '2c0f:f248::/32',
                ];

                trustedProxies.select2('close');
                trustedProxies.empty();

                cloudflareProxies.forEach(function (proxy) {
                    trustedProxies.append(
                        new Option(proxy, proxy, true, true)
                    );
                });

                trustedProxies.trigger('change');
            });

            $('#clear-trusted-proxies').on('click', function () {
                trustedProxies.select2('close');
                trustedProxies.empty().trigger('change');
            });

            trustedProxies.on('change', syncTrustedProxies);

            trustedProxies.closest('form').on('submit', function () {
                trustedProxies.select2('close');
                syncTrustedProxies();
            });

            syncTrustedProxies();
        });
    </script>
@endsection
