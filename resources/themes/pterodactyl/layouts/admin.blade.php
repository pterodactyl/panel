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
        @section('scripts')
            {!! Theme::css('vendor/select2/select2.min.css') !!}
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
                                <a href="#" data-action="control-sidebar" data-toggle="tooltip" data-placement="bottom" title="Quick Access"><i class="fa fa-fighter-jet" style="margin-top:4px;padding-bottom:2px;"></i></a>
                            </li>
                            <li>
                                <li><a href="{{ route('index') }}" data-toggle="tooltip" data-placement="bottom" title="Exit Admin Control"><i class="fa fa-server" style="margin-top:4px;padding-bottom:2px;"></i></a></li>
                            </li>
                            <li>
                                <li><a href="{{ route('auth.logout') }}" data-toggle="tooltip" data-placement="bottom" title="Logout"><i class="fa fa-power-off" style="margin-top:4px;padding-bottom:2px;"></i></a></li>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <aside class="main-sidebar">
                <section class="sidebar">
                    <ul class="sidebar-menu">
                        <li class="header">BASIC ADMINISTRATION</li>
                        <li class="{{ Route::currentRouteName() !== 'admin.index' ?: 'active' }}">
                            <a href="{{ route('admin.index') }}">
                                <i class="fa fa-home"></i> <span>Overview</span>
                            </a>
                        </li>
                        <li class="{{ Route::currentRouteName() !== 'admin.settings' ?: 'active' }}">
                            <a href="{{ route('admin.settings')}}">
                                <i class="fa fa-wrench"></i> <span>Settings</span>
                            </a>
                        </li>
                        <li class="header">MANAGEMENT</li>
                        <li class="{{ Route::currentRouteName() !== 'admin.servers' ?: 'active' }}">
                            <a href="{{ route('admin.servers') }}">
                                <i class="fa fa-server"></i> <span>Servers</span>
                            </a>
                        </li>
                        <li class="{{ Route::currentRouteName() !== 'admin.nodes' ?: 'active' }}">
                            <a href="{{ route('admin.nodes') }}">
                                <i class="fa fa-sitemap"></i> <span>Nodes</span>
                            </a>
                        </li>
                        <li class="{{ Route::currentRouteName() !== 'admin.users' ?: 'active' }}">
                            <a href="{{ route('admin.users') }}">
                                <i class="fa fa-users"></i> <span>Users</span>
                            </a>
                        </li>
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
                Copyright &copy; 2015 - {{ date('Y') }} <a href="https://pterodactyl.io/">Pterodactyl Software</a>.
            </footer>
            <aside class="control-sidebar control-sidebar-dark">
                <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
                    <li class="active"><a href="#control-sidebar-servers-tab" data-toggle="tab"><i class="fa fa-server"></i></a></li>
                    <li><a href="#control-sidebar-nodes-tab" data-toggle="tab"><i class="fa fa-sitemap"></i></a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="control-sidebar-servers-tab">
                        <ul class="control-sidebar-menu">
                            @foreach (Pterodactyl\Models\Server::all() as $s)
                                <li>
                                    <a href="{{ route('admin.servers.view', $s->id) }}">
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
                    <div class="tab-pane" id="control-sidebar-nodes-tab">
                        <ul class="control-sidebar-menu">
                            @foreach (Pterodactyl\Models\Node::with('location')->get() as $n)
                                <li>
                                    <a href="{{ route('admin.nodes.view', $n->id) }}">
                                        <i class="menu-icon fa fa-codepen bg-gray"></i>
                                        <div class="menu-info">
                                            <h4 class="control-sidebar-subheading">{{ $n->name }}</h4>
                                            <p>{{ $n->location->short }}</p>
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
            {!! Theme::js('vendor/select2/select2.full.min.js') !!}
            {!! Theme::js('js/admin/functions.js') !!}
        @show
    </body>
</html>
