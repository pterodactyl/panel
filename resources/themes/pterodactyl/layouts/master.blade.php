{{-- Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com> --}}

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
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(Auth::user()->email)) }}?s=160" class="user-image" alt="User Image">
                                    <span class="hidden-xs">{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</span> <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu" role="menu">
                                    @if(Auth::user()->isRootAdmin())
                                        <li><a href="{{ route('admin.index') }}">@lang('strings.admin_control')</a></li>
                                    @endif
                                    <li><a href="{{ route('auth.logout') }}">@lang('strings.sign_out')</a></li>
                                </ul>
                                {{-- <ul class="dropdown-menu">
                                    <li class="user-header">
                                        <p>
                                            <small>Member since Nov. 2012</small>
                                        </p>
                                    </li>
                                    <li class="user-body">
                                        <div class="row">
                                            <div class="col-xs-4 text-center">
                                                <a href="#">Followers</a>
                                            </div>
                                            <div class="col-xs-4 text-center">
                                                <a href="#">Sales</a>
                                            </div>
                                            <div class="col-xs-4 text-center">
                                                <a href="#">Friends</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="user-footer">
                                        <div class="pull-left">
                                            <a href="{{ route('admin.index') }}" class="btn btn-default btn-flat">Admin Control</a>
                                        </div>
                                        <div class="pull-right">
                                            <a href="{{ route('auth.logout') }}" class="btn btn-default btn-flat">Sign out</a>
                                        </div>
                                    </li>
                                </ul> --}}
                            </li>
                            <li>
                                <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears" style="margin-top:4px;padding-bottom:2px;"></i></a>
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
                            <a href="{{ route('account')}}">
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
                                </a>
                            </li>
                            <li class="treeview
                                @if(in_array(Route::currentRouteName(), ['server.files.index', 'server.files.edit', 'server.files.add']))
                                    active
                                @endif
                            ">
                                <a href="#">
                                    <i class="fa fa-files-o"></i>
                                    <span>@lang('navigation.server.file_management')</span>
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li class="{{ (Route::currentRouteName() !== 'server.files.index' && Route::currentRouteName() !== 'server.files.edit') ?: 'active' }}"><a href="{{ route('server.files.index', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.file_browser')</a></li>
                                    <li class="{{ Route::currentRouteName() !== 'server.files.add' ?: 'active' }}"><a href="{{ route('server.files.add', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.create_file')</a></li>
                                </ul>
                            </li>
                            <li
                                @if(in_array(Route::currentRouteName(), ['server.subusers', 'server.subusers.new', 'server.subusers.view']))
                                    class="active"
                                @endif
                            >
                                <a href="{{ route('server.subusers', $server->uuidShort)}}">
                                    <i class="fa fa-users"></i> <span>Subusers</span>
                                </a>
                            </li>
                            <li
                                @if(in_array(Route::currentRouteName(), ['server.tasks', 'server.tasks.new']))
                                    class="active"
                                @endif
                            >
                                <a href="{{ route('server.tasks', $server->uuidShort)}}">
                                    <i class="fa fa-clock-o"></i> <span>@lang('navigation.server.task_management')</span>
                                    <span class="pull-right-container">
                                        <span class="label label-primary pull-right">{{ \Pterodactyl\Models\Task::select('id')->where('server', $server->id)->where('active', 1)->count() }}</span>
                                    </span>
                                </a>
                            </li>
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
                                    <li class="{{ Route::currentRouteName() !== 'server.settings.allocation' ?: 'active' }}"><a href="{{ route('server.settings.allocation', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.port_allocations')</a></li>
                                    <li class="{{ Route::currentRouteName() !== 'server.settings.sftp' ?: 'active' }}"><a href="{{ route('server.settings.sftp', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.sftp_settings')</a></li>
                                    <li class="{{ Route::currentRouteName() !== 'server.settings.startup' ?: 'active' }}"><a href="{{ route('server.settings.startup', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.startup_parameters')</a></li>
                                    <li class="{{ Route::currentRouteName() !== 'server.settings.databases' ?: 'active' }}"><a href="{{ route('server.settings.databases', $server->uuidShort) }}"><i class="fa fa-angle-right"></i> @lang('navigation.server.databases')</a></li>
                                </ul>
                            </li>
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
                <div class="pull-right hidden-xs small text-gray">
                    <strong>v</strong> {{ config('app.version') }}
                </div>
                Copyright &copy; 2015 - {{ date('Y') }} <a href="https://pterodactyl.io/">Pterodactyl Software &amp; Design</a>.
            </footer>
            <aside class="control-sidebar control-sidebar-dark">
                <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
                    <li><a href="#control-sidebar-servers-tab" data-toggle="tab"><i class="fa fa-server"></i></a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="control-sidebar-servers-tab">
                        <ul class="control-sidebar-menu">
                            @foreach (Pterodactyl\Models\Server::getUserServers() as $s)
                                <li>
                                    <a
                                        @if(isset($server) && isset($node))
                                            @if($server->uuidShort === $s->uuidShort)
                                                class="active"
                                            @endif
                                        @endif
                                    href="{{ route('server.index', $s->uuidShort) }}">
                                        @if($s->owner === Auth::user()->id)
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
                </div>
            </aside>
            <div class="control-sidebar-bg"></div>
        </div>
        @section('footer-scripts')
            {!! Theme::js('js/laroute.js') !!}
            {!! Theme::js('js/vendor/jquery/jquery.min.js') !!}
            {!! Theme::js('vendor/sweetalert/sweetalert.min.js') !!}
            {!! Theme::js('vendor/bootstrap/bootstrap.min.js') !!}
            {!! Theme::js('vendor/slimscroll/jquery.slimscroll.min.js') !!}
            {!! Theme::js('vendor/adminlte/app.min.js') !!}
            {!! Theme::js('js/vendor/socketio/socket.io.min.js') !!}
            {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js') !!}
        @show
    </body>
</html>
