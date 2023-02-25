
<div class="tab-pane mt-3" id="system">
    <form method="POST" enctype="multipart/form-data" class="mb-3"
        action="{{ route('admin.settings.update.systemsettings') }}">
        @csrf
        @method('PATCH')

        <div class="row">
            {{-- System --}}
            <div class="col-md-3 px-3">
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>{{ __('System') }}</h1>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control mb-1 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <input value="true" id="show-tos" name="show-tos"
                                       {{ config('SETTINGS::SYSTEM:SHOW_TOS') == 'true' ? 'checked' : '' }}
                                       type="checkbox">
                                <label for="show-tos">{{ __('Show Terms of Service') }} </label>
                            </div>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                               data-content="{{ __('Show the TOS link in the footer of every page. <br> Edit the content in <b>'.Qirolab\Theme\Theme::path($path = "views").'/information/tos-content.blade.php</b>') }}"
                               class="fas fa-info-circle"></i>
                        </div>
                    </div>
                    <div class="custom-control mb-1 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <input value="true" id="show-imprint" name="show-imprint"
                                    {{ config('SETTINGS::SYSTEM:SHOW_IMPRINT') == 'true' ? 'checked' : '' }}
                                    type="checkbox">
                                <label for="show-imprint">{{ __('Show Imprint') }} </label>
                            </div>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Show the imprint link in the footer of every page. <br> Edit the content in <b>'.Qirolab\Theme\Theme::path($path = "views").'/resources/views/information/imprint-content.blade.php</b>') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                    </div>
                    <div class="custom-control mb-1 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <input value="true" id="show-privacy" name="show-privacy"
                                    {{ config('SETTINGS::SYSTEM:SHOW_PRIVACY') == 'true' ? 'checked' : '' }}
                                    type="checkbox">
                                <label for="show-privacy">{{ __('Show Privacy Policy') }} </label>
                            </div>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Show the privacy policy link in the footer of every page. <br> Edit the content in <b>'.Qirolab\Theme\Theme::path($path = "views").'/resources/views/information/privacy-content.blade.php</b>') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                    </div>
                    <div class="custom-control mb-1 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <input value="true" id="register-ip-check" name="register-ip-check"
                                    {{ config('SETTINGS::SYSTEM:REGISTER_IP_CHECK') == 'true' ? 'checked' : '' }}
                                    type="checkbox">
                                <label for="register-ip-check">{{ __('Register IP Check') }} </label>
                            </div>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Prevent users from making multiple accounts using the same IP address.') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <label for="credits-display-name">{{ __('Credits Display Name') }}</label>
                        <input x-model="credits-display-name" id="credits-display-name" name="credits-display-name"
                            type="text" value="{{ config('SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME', 'Credits') }}"
                            class="form-control @error('credits-display-name') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control p-0 mb-3">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="phpmyadmin-url">{{ __('PHPMyAdmin URL') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Enter the URL to your PHPMyAdmin installation. <strong>Without a trailing slash!</strong>') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="phpmyadmin-url" id="phpmyadmin-url" name="phpmyadmin-url" type="text"
                            value="{{ config('SETTINGS::MISC:PHPMYADMIN:URL') }}"
                            class="form-control @error('phpmyadmin-url') is-invalid @enderror">
                    </div>
                    <div class="custom-control p-0 mb-3">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="pterodactyl-url">{{ __('Pterodactyl URL') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Enter the URL to your Pterodactyl installation. <strong>Without a trailing slash!</strong>') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="pterodactyl-url" id="pterodactyl-url" name="pterodactyl-url" type="text"
                            value="{{ config('SETTINGS::SYSTEM:PTERODACTYL:URL') }}"
                            class="form-control @error('pterodactyl-url') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="per-page-limit">{{ __('Pterodactyl API perPage limit') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true" type="number" min="0" max="99999999"
                                data-content="{{ __('The Pterodactyl API perPage limit. It is necessary to set it higher than your server count.') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="per-page-limit" id="per-page-limit" name="per-page-limit" type="number"
                            value="{{ config('SETTINGS::SYSTEM:PTERODACTYL:PER_PAGE_LIMIT') }}"
                            class="form-control @error('per-page-limit') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control p-0 mb-3">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="pterodactyl-api-key">{{ __('Pterodactyl API Key') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Enter the API Key to your Pterodactyl installation.') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="pterodactyl-api-key" id="pterodactyl-api-key" name="pterodactyl-api-key"
                            type="text" value="{{ config('SETTINGS::SYSTEM:PTERODACTYL:TOKEN') }}"
                            class="form-control @error('pterodactyl-api-key') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control p-0 mb-3">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label
                                for="pterodactyl-admin-api-key">{{ __('Pterodactyl Admin-Account API Key') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Enter the Client-API Key to a Pterodactyl-Admin-User here.') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="pterodactyl-admin-api-key" id="pterodactyl-admin-api-key"
                            name="pterodactyl-admin-api-key" type="text"
                            value="{{ config('SETTINGS::SYSTEM:PTERODACTYL:ADMIN_USER_TOKEN') }}"
                            class="form-control @error('pterodactyl-admin-api-key') is-invalid @enderror" required>
                        @error('pterodactyl-admin-api-key')
                            <div class="text-danger">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <a href="{{ route('admin.settings.checkPteroClientkey') }}"> <button type="button"
                            class="btn btn-secondary">{{ __('Test API') }}</button></a>
                </div>

            </div>

            {{-- User --}}
            <div class="col-md-3 px-3">
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>{{ __('User') }}</h1>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control mb-1 p-0">
                        <input value="true" id="force-discord-verification" name="force-discord-verification"
                            {{ config('SETTINGS::USER:FORCE_DISCORD_VERIFICATION') == 'true' ? 'checked' : '' }}
                            type="checkbox">
                        <label for="force-discord-verification">{{ __('Force Discord verification') }}
                        </label>
                    </div>
                    <div class="custom-control mb-1 p-0">
                        <input value="true" id="force-email-verification" name="force-email-verification"
                            {{ config('SETTINGS::USER:FORCE_EMAIL_VERIFICATION') == 'true' ? 'checked' : '' }}
                            type="checkbox">
                        <label for="force-email-verification">{{ __('Force E-Mail verification') }} </label>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <input value="true" id="enable-disable-new-users" name="enable-disable-new-users"
                            {{ config('SETTINGS::SYSTEM:CREATION_OF_NEW_USERS') == 'true' ? 'checked' : '' }}
                            type="checkbox">
                        <label for="enable-disable-new-users">{{ __('Creation of new users') }} </label>
                        <i data-toggle="popover" data-trigger="hover" data-html="true" class="fas fa-info-circle"
                            data-content="{{ __('If unchecked, it will disable the registration of new users in the system, and this will also apply to the API.') }}">
                        </i>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <label for="initial-credits">{{ __('Initial Credits') }}</label>
                        <input x-model="initial-credits" id="initial-credits" name="initial-credits" type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:INITIAL_CREDITS') }}"
                            class="form-control @error('initial-credits') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <label for="initial-server-limit">{{ __('Initial Server Limit') }}</label>
                        <input x-model="initial-server-limit" id="initial-server-limit" name="initial-server-limit" type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:INITIAL_SERVER_LIMIT') }}"
                            class="form-control @error('initial-server-limit') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <label for="credits-reward-amount-discord">{{ __('Credits Reward Amount - Discord') }}</label>
                        <input x-model="credits-reward-amount-discord" id="credits-reward-amount-discord"
                            name="credits-reward-amount-discord" type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_DISCORD') }}"
                            class="form-control @error('credits-reward-amount-discord') is-invalid @enderror" required>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <label for="credits-reward-amount-email">{{ __('Credits Reward Amount - E-Mail') }}</label>
                        <input x-model="credits-reward-amount-email" id="credits-reward-amount-email"
                            name="credits-reward-amount-email" type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL') }}"
                            class="form-control @error('credits-reward-amount-email') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <label for="server-limit-discord">{{ __('Server Limit Increase - Discord') }}</label>
                        <input x-model="server-limit-discord" id="server-limit-discord" name="server-limit-discord"
                            type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD') }}"
                            class="form-control @error('server-limit-discord') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <label for="server-limit-email">{{ __('Server Limit Increase - E-Mail') }}</label>
                        <input x-model="server-limit-email" id="server-limit-email" name="server-limit-email"
                            type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL') }}"
                            class="form-control @error('server-limit-email') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <label for="server-limit-purchase">{{ __('Server Limit after Credits Purchase') }}</label>
                        <input x-model="server-limit-purchase" id="server-limit-purchase"
                            name="server-limit-purchase" type="number" min="0" max="99999999"
                            value="{{ config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') }}"
                            class="form-control @error('server-limit-purchase') is-invalid @enderror" required>
                    </div>
                </div>
            </div>

            {{-- Server --}}
            <div class="col-md-3 px-3">
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>{{ __('Server') }}</h1>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control mb-1 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <input value="true" id="enable-upgrade" name="enable-upgrade"
                                    {{ config('SETTINGS::SYSTEM:ENABLE_UPGRADE') == 'true' ? 'checked' : '' }}
                                    type="checkbox">
                                <label for="enable-upgrade">{{ __('Enable upgrade/downgrade of servers') }} </label>
                            </div>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('Allow upgrade/downgrade to a new product for the given server') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control mb-1 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <input value="true" id="enable-disable-servers" name="enable-disable-servers"
                                    {{ config('SETTINGS::SYSTEM:CREATION_OF_NEW_SERVERS') == 'true' ? 'checked' : '' }}
                                    type="checkbox">
                                <label for="enable-disable-servers">{{ __('Creation of new servers') }} </label>
                            </div>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('If unchecked, it will disable the creation of new servers for regular users and system moderators, this has no effect for administrators.') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control mb-3 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="allocation-limit">{{ __('Server Allocation Limit') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('The maximum amount of allocations to pull per node for automatic deployment, if more allocations are being used than this limit is set to, no new servers can be created!') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="allocation-limit" id="allocation-limit" name="allocation-limit"
                        type="number" min="0" max="99999999" value="{{ config('SETTINGS::SERVER:ALLOCATION_LIMIT') }}"
                            class="form-control @error('allocation-limit') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="minimum-credits">{{ __('Minimum credits') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="{{ __('The minimum amount of credits user has to have to create a server. Can be overridden by package limits.') }}"
                                class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="minimum-credits" id="minimum-credits" name="minimum-credits"
                        type="number" min="0" max="99999999" value="{{ config('SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER') }}"
                            class="form-control @error('minimum-credits') is-invalid @enderror" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>{{ __('SEO') }}</h1>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control mb-3 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="seo-title">{{ __('SEO Title') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                               data-content="{{ __('An SEO title tag must contain your target keyword. This tells both Google and searchers that your web page is relevant to this search query!') }}"
                               class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="seo-title" id="seo-title" name="seo-title"
                               type="text" value="{{ config('SETTINGS::SYSTEM:SEO_TITLE') }}"
                               class="form-control @error('seo-title') is-invalid @enderror" required>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <div class="col m-0 p-0 d-flex justify-content-between align-items-center">
                            <label for="seo-description">{{ __('SEO Description') }}</label>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                               data-content="{{ __('The SEO site description represents your homepage. Search engines show this description in search results for your homepage if they dont find content more relevant to a visitors search terms.') }}"
                               class="fas fa-info-circle"></i>
                        </div>
                        <input x-model="seo-description" id="seo-description" name="seo-description"
                               type="text" value="{{ config('SETTINGS::SYSTEM:SEO_DESCRIPTION') }}"
                               class="form-control @error('seo-description') is-invalid @enderror" required>
                    </div>
                </div>
            </div>



            {{-- Design --}}
            <div class="col-md-3 px-3">
                <div class="row mb-2">
                    <div class="col text-center">
                        <h1>{{ __('Design') }}</h1>
                    </div>
                </div>
                <div class="custom-control mb-3 p-0">
                    <label for="alert-type">{{ __('Theme') }}</label>
                    <select id="theme" style="width:100%" class="custom-select" name="theme" required
                            autocomplete="off" @error('theme') is-invalid @enderror>
                        @foreach($themes as $theme)
                        <option value="{{$theme}}" @if ($active_theme == $theme) selected @endif>{{$theme}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="custom-control mb-3 p-0">
                    <input value="true" id="enable-login-logo" name="enable-login-logo"
                        {{ config('SETTINGS::SYSTEM:ENABLE_LOGIN_LOGO') == 'true' ? 'checked' : '' }} type="checkbox">
                    <label for="enable-login-logo">{{ __('Enable Logo on Loginpage') }} </label>
                </div>
                <div class="form-group">
                    <div class="custom-file mb-3 mt-3">
                        <input type="file" accept="image/png,image/jpeg,image/jpg" class="custom-file-input"
                            name="icon" id="icon">
                        <label class="custom-file-label selected"
                            for="icon">{{ __('Select panel icon') }}</label>
                    </div>
                    @error('icon')
                        <span class="text-danger">
                            {{ $message }}
                        </span>
                    @enderror

                    <div class="form-group">
                        <div class="custom-file mb-3 mt-3">
                            <input type="file" accept="image/png,image/jpeg,image/jpg" class="custom-file-input"
                                name="logo" id="logo">
                            <label class="custom-file-label selected"
                                for="logo">{{ __('Select Login-page Logo') }}</label>
                        </div>
                        @error('logo')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>
                    <div class="form-group">
                        <div class="custom-file mb-3">
                            <input type="file" accept="image/x-icon" class="custom-file-input" name="favicon"
                                id="favicon">
                            <label class="custom-file-label selected"
                                for="favicon">{{ __('Select panel favicon') }}</label>
                        </div>
                        @error('favicon')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

            </div>
        </div>
            <div class="row">
                <div class="col-md-3">
                    {{-- ALERT --}}
                    <div class="row mb-2">
                        <div class="col text-center">
                            <h1>Alert</h1>
                        </div>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <input value="true" id="alert-enabled" name="alert-enabled"
                               {{ config('SETTINGS::SYSTEM:ALERT_ENABLED') == 'true' ? 'checked' : '' }} type="checkbox">
                        <label for="alert-enabled">{{ __('Enable the Alert Message on Homepage') }} </label>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <label for="alert-type">{{ __('Alert Color') }}</label>
                        <select id="alert-type" style="width:100%" class="custom-select" name="alert-type" required
                                autocomplete="off" @error('alert-type') is-invalid @enderror>
                            <option value="primary" @if (config('SETTINGS::SYSTEM:ALERT_TYPE') == "primary") selected
                                @endif>{{ __("Blue") }}</option>
                            <option value="secondary" @if (config('SETTINGS::SYSTEM:ALERT_TYPE') == "secondary") selected
                                @endif>{{ __("Grey") }}</option>
                            <option value="success" @if (config('SETTINGS::SYSTEM:ALERT_TYPE') == "success") selected
                                @endif>{{ __("Green") }}</option>
                            <option value="danger" @if (config('SETTINGS::SYSTEM:ALERT_TYPE') == "danger") selected
                                @endif>{{ __("Red") }}</option>
                            <option value="warning" @if (config('SETTINGS::SYSTEM:ALERT_TYPE') == "warning") selected
                                @endif>{{ __("Orange") }}</option>
                            <option value="info" @if (config('SETTINGS::SYSTEM:ALERT_TYPE') == "info") selected
                                @endif>{{ __("Cyan") }}</option>
                        </select>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <label for="alert-message">{{ __('Alert Message (HTML might be used)') }}</label>
                        <textarea x-model="alert-message" id="alert-message" name="alert-message"
                                  class="form-control @error('alert-message') is-invalid @enderror">
                        {{ config('SETTINGS::SYSTEM:ALERT_MESSAGE', '') }}
                        </textarea>
                        @error('alert-message')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Homepage Text --}}
                    <div class="row mb-2">
                        <div class="col text-center">
                            <h1>{{__("Message of the day")}}</h1>
                        </div>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <input value="true" id="motd-enabled" name="motd-enabled"
                               {{ config('SETTINGS::SYSTEM:MOTD_ENABLED') == 'true' ? 'checked' : '' }} type="checkbox">
                        <label for="motd-enabled">{{ __('Enable the MOTD on the Homepage') }} </label>
                    </div>
                    <div class="custom-control mb-3 p-0">
                        <input value="true" id="usefullinks-enabled" name="usefullinks-enabled"
                               {{ config('SETTINGS::SYSTEM:USEFULLINKS_ENABLED') == 'true' ? 'checked' : '' }} type="checkbox">
                        <label for="usefullinks-enabled">{{ __('Enable the Useful-Links section') }} </label>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <label for="alert-message">{{ __('MOTD-Text') }}</label>
                        <textarea x-model="motd-message" id="motd-message" name="motd-message"
                                  class="form-control @error('motd-message') is-invalid @enderror">
                        {{ config('SETTINGS::SYSTEM:MOTD_MESSAGE', '') }}
                        </textarea>
                        @error('motd-message')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <button class="btn btn-primary ml-3 mt-3">{{ __('Submit') }}</button>
            </div>
    </form>
</div>
<script>tinymce.init({selector:'textarea',skin: "oxide-dark",
        content_css: "dark",branding: false,  height: 500,
        plugins: ['image','link'],});
</script>

