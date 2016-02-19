<!DOCTYPE html>
<html lang="en">
<head>
    @section('scripts')
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex">
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        {!! Theme::css('dist/semantic.min.css') !!}
        {!! Theme::js('dist/semantic.min.js') !!}
    @show
    <title>{{ Settings::get('company') }} - @yield('title')</title>
</head>
<body>
    <br>
    <div class="ui container">
        @if(strpos(config('app.version'), 'beta') || strpos(config('app.version'), 'alpha') || strpos(config('app.version'), 'dev') !== false)
            <div class="ui warning message">
                <div class="header">Unstable Version</div>
                You are running a developmental build of Pterodactyl Panel. Do not under any circumstances run this on a live environment. We cannot be held liable for any damages caused to your system by this panel.
            </div>
        @endif
        <div class="ui top inverted menu">
            <div class="header item">{{ Settings::get('company') }}</div>
            @section('right-nav')
                <div class="right menu">
                    <div class="ui dropdown simple icon item">
                        <i class="globe icon"></i>&nbsp;{{ trans('strings.language') }}
                        <div class="menu">
                            <a href="/language/de" class="item">Deutsch</a>
                            <a href="/language/en" class="item">English</a>
                            <a href="/language/es" class="item">Espa&ntilde;ol</a>
                            <a href="/language/fr" class="item">Fran&ccedil;ais</a>
                            <a href="/language/it" class="item">Italiano</a>
                            <a href="/language/pl" class="item">Polski</a>
                            <a href="/language/pt" class="item">Portugu&ecirc;s</a>
                            <a href="/language/ru" class="item">&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;</a>
                            <a href="/language/se" class="item">Svenska</a>
                            <a href="/language/zh" class="item">&#20013;&#22269;&#30340;的</a>
                        </div>
                    </div>
                    @if (null !== Auth::user() && Auth::user()->root_admin == 1)
                        <a href="/admin" class="item"><i class="dashboard icon"></i>Admin CP</a>
                    @endif
                    <a href="/auth/logout" class="item"><i class="sign out icon"></i>Sign Out</a>
                </div>
            @show
        </div>
        <div class="ui grid">
            <div class="row">
                <div class="four wide column">
                    <div class="ui fluid container">
                        @section('sidebar')
                            <div class="ui vertical pointing fluid menu" id="sidebar">
                                <a class="header item">{{ trans('pagination.sidebar.account_controls') }}</a>
                                <a href="/account" class="item" data-tab="/account">{{ trans('pagination.sidebar.account_settings') }}</a>
                                <a href="/account/totp" class="item" data-tab="/account/totp">{{ trans('pagination.sidebar.account_security') }}</a>
                                <a href="/" class="item" data-tab="/">{{ trans('pagination.sidebar.servers') }}</a>
                            </div>
                        @show
                    </div>
                </div>
                <div class="twelve wide column">
                    <div class="ui fluid container">
                        @section('resp-errors')
                            @if (count($errors) > 0)
                                <div class="ui warning message">
                                    <i class="close icon"></i>
                                    <div class="header">{{ trans('strings.whoops') }}!</strong> {{ trans('auth.errorencountered') }}</div>
                                    <ul class="list">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @show
                        @section('resp-alerts')
                            @foreach (Alert::getMessages() as $type => $messages)
                                @foreach ($messages as $message)
                                    <div class="ui {{ $type }} message">
                                        <i class="close icon"></i>
                                        <div class="header">{{ trans('strings.whoops') }}!</strong> {{ trans('auth.errorencountered') }}</div>
                                        {!! $message !!}
                                    </div>
                                @endforeach
                            @endforeach
                        @show
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
        <div class="ui divider"></div>
        <div class="ui fluid container">
            Copyright &copy; 2015 - {{ date('Y') }} <a href="https://github.com/Pterodactyl/Panel" target="_blank">Pterodactyl Software &amp; Design</a>.<br />
            Pterodactyl is licensed under a <a href="https://opensource.org/licenses/MIT" target="_blank">MIT</a> license. <!-- Please do not remove this license notice. We can't stop you though... :) -->
        </div>
    </div>
    <script>
        $('.message .close').on('click', function() {
            $(this).closest('.message').transition('fade');
          });
        $('#sidebar a[href$="' + window.location.pathname +'"').addClass('active');
    </script>
</body>
</html>
