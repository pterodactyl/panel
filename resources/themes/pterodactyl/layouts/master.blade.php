{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ Settings::get('company', 'Pterodactyl') }} - @yield('title')</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="_token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="/favicons/manifest.json">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#367fa9">

        @include('layouts.scripts')

        @section('scripts')
            {!! Theme::css('vendor/bootstrap/bootstrap.min.css') !!}
            {!! Theme::css('vendor/adminlte/admin.min.css') !!}
            {!! Theme::css('vendor/adminlte/colors/skin-blue.min.css') !!}
            {!! Theme::css('vendor/sweetalert/sweetalert.min.css') !!}
            {!! Theme::css('vendor/animate/animate.min.css') !!}
            {!! Theme::css('css/pterodactyl.css') !!}
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">

            <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
        @show
    </head>
    <body class="hold-transition skin-blue fixed sidebar-mini">
        <div class="wrapper">
            <header class="main-header">
                <a href="{{ route('index') }}" class="logo">
                    <span>{{ Settings::get('company', 'Pterodactyl') }}</span>
                </a>
                <nav class="navbar navbar-static-top">
                    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </a>
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <li class="dropdown user-menu">
                                <a href="{{ route('account') }}" class="dropdown-toggle" data-toggle="dropdown">
                                    <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(Auth::user()->email)) }}?s=160" class="user-image" alt="User Image">
                                    <span class="hidden-xs">{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" data-action="control-sidebar" data-toggle="tooltip" data-placement="bottom" title="{{ @trans('strings.servers') }}"><i class="fa fa-server"></i></a>
                            </li>
                            @if(Auth::user()->isRootAdmin())
                                <li>
                                    <li><a href="{{ route('admin.index') }}" data-toggle="tooltip" data-placement="bottom" title="{{ @trans('strings.admin_cp') }}"><i class="fa fa-gears"></i></a></li>
                                </li>
                            @endif
                            <li>
                                <li><a href="{{ route('auth.logout') }}" data-toggle="tooltip" data-placement="bottom" title="{{ @trans('strings.logout') }}"><i class="fa fa-power-off"></i></a></li>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <aside class="main-sidebar">
                <section class="sidebar">
                    @if (isset($server->name) && isset($node->name))
                        <div class="user-panel">
                            <div class="info">
                              <p>{{ $server->name }}</p>
                              <a href="#" id="server_status_icon"><i class="fa fa-circle text-default"></i> Checking...</a>
                            </div>
                        </div>
                    @endif
                    <ul class="sidebar-menu">
                        <li class="header">@lang('navigation.account.header')</li>
                        <li class="{{ Route::currentRouteName() !== 'account' ?: 'active' }}">
                            <a href="{{ route('account') }}">
                                <i class="fa fa-user"></i> <span>@lang('navigation.account.my_account')</span>
                            </a>
                        </li>
                        <li class="{{ Route::currentRouteName() !== 'account.security' ?: 'active' }}">
                            <a href="{{ route('account.security')}}">
                                <i class="fa fa-lock"></i> <span>@lang('navigation.account.security_controls')</span>
                            </a>
                        </li>
                        <li class="{{ (Route::currentRouteName() !== 'account.api' && Route::currentRouteName() !== 'account.api.new') ?: 'active' }}">
                            <a href="{{ route('account.api')}}">
                                <i class="fa fa-code"></i> <span>@lang('navigation.account.api_access')</span>
                            </a>
                        </li>
                        <li class="{{ Route::currentRouteName() !== 'index' ?: 'active' }}">
                            <a href="{{ route('index')}}">
                                <i class="fa fa-server"></i> <span>@lang('navigation.account.my_servers')</span>
                            </a>
                        </li>
                        @if (isset($server->name) && isset($node->name))
                            <li class="header">@lang('navigation.server.header')</li>
                            <li class="{{ Route::currentRouteName() !== 'server.index' ?: 'active' }}">
                                <a href="{{ route('server.index', $server->uuidShort) }}">
                                    <i class="fa fa-terminal"></i> <span>@lang('navigation.server.console')</span>
                                    <span class="pull-right-container muted muted-hover" href="{{ route('server.console', $server->uuidShort) }}" id="console-popout">
                                        <span class="label label-default pull-right" style="padding: 3px 5px 2px 5px;">
                                            <i class="fa fa-external-link"></i>
                                        </span>
                                    </span>
                                </a>
                            </li>
                            @can('list-files', $server)
                                <li
                                    @if(starts_with(Route::currentRouteName(), 'server.files'))
                                        class="active"
                                    @endif
                                >
                                    <a href="{{ route('server.files.index', $server->uuidShort) }}">
                                        <i class="fa fa-files-o"></i> <span>@lang('navigation.server.file_management')</span>
                                    </a>
                                </li>
                            @endcan
                            @can('list-subusers', $server)
                                <li
                                    @if(in_array(Route::currentRouteName(), ['server.subusers', 'server.subusers.new', 'server.subusers.view']))
                                        class="active"
                                    @endif
                                >
                                    <a href="{{ route('server.subusers', $server->uuidShort)}}">
                                        <i class="fa fa-users"></i> <span>@lang('navigation.server.subusers')</span>
                                    </a>
                                </li>
                            @endcan
                            @can('list-tasks', $server)
                                <li
                                    @if(in_array(Route::currentRouteName(), ['server.tasks', 'server.tasks.new']))
                                        class="active"
                                    @endif
                                >
                                    <a href="{{ route('server.tasks', $server->uuidShort)}}">
                                        <i class="fa fa-clock-o"></i> <span>@lang('navigation.server.task_management')</span>
                                        <span class="pull-right-container">
                                            <span class="label label-primary pull-right">{{ \Pterodactyl\Models\Task::select('id')->where('server_id', $server->id)->where('active', 1)->count() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endcan
                            @if(Gate::allows('view-startup', $server) || Gate::allows('view-sftp', $server) || Gate::allows('view-databases', $server) || Gate::allows('view-allocation', $server))
                                <li class="treeview
                                    @if(in_array(Route::currentRouteName(), ['server.settings.sftp', 'server.settings.databases', 'server.settings.startup', 'server.settings.allocation']))
                                        active
                                    @endif
                                ">
                                    <a href="#">
                                        <i class="fa fa-gears"></i>
                                        <span>@lang('navigation.server.configuration')</span>
                                        <span class="pull-right-container">
                                            <i class="fa fa-angle-left pull-right"></i>
                                        </span>
                                    </a>
                                    <ul class="treeview-menu">
                                        @can('view-allocation', $server)
                                            <li class="{{ Route::currentRouteName() !== 'server.settings.allocation' ?: 'active' }}"><a href="{{ route('server.settings.allocation', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.port_allocations')</a></li>
                                        @endcan
                                        @can('view-sftp', $server)
                                            <li class="{{ Route::currentRouteName() !== 'server.settings.sftp' ?: 'active' }}"><a href="{{ route('server.settings.sftp', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.sftp_settings')</a></li>
                                        @endcan
                                        @can('view-startup', $server)
                                            <li class="{{ Route::currentRouteName() !== 'server.settings.startup' ?: 'active' }}"><a href="{{ route('server.settings.startup', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.startup_parameters')</a></li>
                                        @endcan
                                        @can('view-databases', $server)
                                            <li class="{{ Route::currentRouteName() !== 'server.settings.databases' ?: 'active' }}"><a href="{{ route('server.settings.databases', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.databases')</a></li>
                                        @endcan
                                    </ul>
                                </li>
                            @endif
                        @endif
                    </ul>
                </section>
            </aside>
            <div class="content-wrapper">
                <section class="content-header">
                    @yield('content-header')
                </section>
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            @if (count($errors) > 0)
                                <div class="callout callout-danger">
                                    @lang('base.validation_error')<br><br>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @foreach (Alert::getMessages() as $type => $messages)
                                @foreach ($messages as $message)
                                    <div class="callout callout-{{ $type }} alert-dismissable" role="alert">
                                        {!! $message !!}
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    @yield('content')
                </section>
            </div>
            <footer class="main-footer">
                <div class="pull-right small text-gray" style="margin-right:10px;margin-top:-7px;">
                    <strong><i class="fa fa-fw {{ $appIsGit ? 'fa-git-square' : 'fa-code-fork' }}"></i></strong> {{ $appVersion }}<br />
                    <strong><i class="fa fa-fw fa-clock-o"></i></strong> {{ round(microtime(true) - LARAVEL_START, 3) }}s
                </div>
                Copyright &copy; 2015 - {{ date('Y') }} <a href="https://pterodactyl.io/">Pterodactyl Software</a>.
            </footer>
            <aside class="control-sidebar control-sidebar-dark">
                <div class="tab-content">
                    <ul class="control-sidebar-menu">
                        @foreach (Auth::user()->access(null)->get() as $s)
                            <li>
                                <a
                                    @if(isset($server) && isset($node))
                                        @if($server->uuidShort === $s->uuidShort)
                                            class="active"
                                        @endif
                                    @endif
                                href="{{ route('server.index', $s->uuidShort) }}">
                                    @if($s->owner_id === Auth::user()->id)
                                        <i class="menu-icon fa fa-user bg-blue"></i>
                                    @else
                                        <i class="menu-icon fa fa-user-o bg-gray"></i>
                                    @endif
                                    <div class="menu-info">
                                        <h4 class="control-sidebar-subheading">{{ $s->name }}</h4>
                                        <p>{{ $s->username }}</p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
            <div class="control-sidebar-bg"></div>
        </div>
        @section('footer-scripts')
            {!! Theme::js('vendor/terminal/keyboard.polyfill.js') !!}
            <script>keyboardeventKeyPolyfill.polyfill();</script>

            {!! Theme::js('js/laroute.js') !!}
            {!! Theme::js('vendor/jquery/jquery.min.js') !!}
            {!! Theme::js('vendor/sweetalert/sweetalert.min.js') !!}
            {!! Theme::js('vendor/bootstrap/bootstrap.min.js') !!}
            {!! Theme::js('vendor/slimscroll/jquery.slimscroll.min.js') !!}
            {!! Theme::js('vendor/adminlte/app.min.js') !!}
            {!! Theme::js('vendor/socketio/socket.io.min.js') !!}
            {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js') !!}
            {!! Theme::js('js/autocomplete.js') !!}
            @if(config('pterodactyl.lang.in_context'))
                {!! Theme::js('vendor/phraseapp/phraseapp.js') !!}
            @endif
        @show
    </body>
</html>
