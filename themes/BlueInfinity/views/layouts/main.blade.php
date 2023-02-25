<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="{{ config('SETTINGS::SYSTEM:SEO_TITLE') }}" property="og:title">
    <meta content="{{ config('SETTINGS::SYSTEM:SEO_DESCRIPTION') }}" property="og:description">
    <meta content='{{ \Illuminate\Support\Facades\Storage::disk('public')->exists('logo.png') ? asset('storage/logo.png') : asset('images/controlpanel_logo.png') }}' property="og:image">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon"
          href="{{ \Illuminate\Support\Facades\Storage::disk('public')->exists('favicon.ico') ? asset('storage/favicon.ico') : asset('favicon.ico') }}"
          type="image/x-icon">

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- <link rel="stylesheet" href="{{asset('css/adminlte.min.css')}}"> --}}
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.24/datatables.min.css" />

    {{-- summernote --}}
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.min.css') }}">

    {{-- datetimepicker --}}
    <link rel="stylesheet"
          href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">

    {{-- select2 --}}
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">

    <link rel="preload" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}" as="style"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    </noscript>
    <script src="{{ asset('js/app.js') }}"></script>
    <!-- tinymce -->
    <script src={{ asset('plugins/tinymce/js/tinymce/tinymce.min.js') }}></script>
    <link rel="stylesheet" href="{{ asset('themes/BlueInfinity/app.css') }}">
</head>

<body class="sidebar-mini layout-fixed dark-mode" style="height: auto;">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header sticky-top navbar navbar-expand navbar-dark navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                        class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('home') }}" class="nav-link"><i
                        class="fas fa-home mr-2"></i>{{ __('Home') }}</a>
            </li>
            @if (config('SETTINGS::DISCORD:INVITE_URL'))
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ config('SETTINGS::DISCORD:INVITE_URL') }}" class="nav-link" target="__blank"><i
                            class="fab fa-discord mr-2"></i>{{ __('Discord') }}</a>
                </li>
            @endif

            <!-- Language Selection -->
            @if (config('SETTINGS::LOCALE:CLIENTS_CAN_CHANGE') == 'true')
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="languageDropdown" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                            <span class="mr-1 d-lg-inline text-gray-600">
                                <small><i class="fa fa-language mr-2"></i></small>{{ __('Language') }}
                            </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                         aria-labelledby="changeLocale">
                        <form method="post" action="{{ route('changeLocale') }}" class="nav-item text-center">
                            @csrf
                            @foreach (explode(',', config('SETTINGS::LOCALE:AVAILABLE')) as $key)
                                <button class="dropdown-item" name="inputLocale" value="{{ $key }}">
                                    {{ __($key) }}
                                </button>
                            @endforeach

                        </form>
                    </div>
                </li>
                <!-- End Language Selection -->
            @endif
            @foreach($useful_links as $link)
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ $link->link }}" class="nav-link" target="__blank"><i
                            class="{{$link->icon}}"></i> {{ $link->title }}</a>
                </li>
            @endforeach
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    @if (Auth::user()->unreadNotifications->count() != 0)
                        <span
                            class="badge badge-warning navbar-badge">{{ Auth::user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">{{ Auth::user()->unreadNotifications->count() }}
                            {{ __('Notifications') }}</span>
                    <div class="dropdown-divider"></div>

                    @foreach (Auth::user()->unreadNotifications->sortBy('created_at')->take(5) as $notification)
                        <a href="{{ route('notifications.show', $notification->id) }}" class="dropdown-item">
                                <span class="d-inline-block text-truncate" style="max-width: 150px;"><i
                                        class="fas fa-envelope mr-2"></i>{{ $notification->data['title'] }}</span>
                            <span
                                class="float-right text-muted text-sm">{{ $notification->created_at->longAbsoluteDiffForHumans() }}
                                    ago</span>
                        </a>
                    @endforeach

                    <div class="dropdown-divider"></div>
                    <a href="{{ route('notifications.index') }}"
                       class="dropdown-item dropdown-footer">{{ __('See all Notifications') }}</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('notifications.readAll') }}"
                       class="dropdown-item dropdown-footer">{{ __('Mark all as read') }}</a>
                </div>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                        <span class="mr-1 d-lg-inline text-gray-600">
                            <small><i class="fas fa-coins mr-2"></i></small>{{ Auth::user()->credits() }}
                        </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                     aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="{{ route('store.index') }}">
                        <i class="fas fa-coins fa-sm fa-fw mr-2 text-gray-400"></i>
                        {{ __('Store') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" data-toggle="modal" data-target="#redeemVoucherModal"
                       href="javascript:void(0)">
                        <i class="fas fa-money-check-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        {{ __('Redeem code') }}
                    </a>
                </div>
            </li>

            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-1 d-lg-inline text-gray-600 small">
                            {{ Auth::user()->name }}
                            <img width="28px" height="28px" class="rounded-circle ml-1"
                                 src="{{ Auth::user()->getAvatar() }}">
                        </span>
                </a>
                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                     aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="{{ route('profile.index') }}">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                        {{ __('Profile') }}
                    </a>
                    {{-- <a class="dropdown-item" href="#"> --}}
                    {{-- <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i> --}}
                    {{-- Activity Log --}}
                    {{-- </a> --}}
                    @if (session()->get('previousUser'))
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('users.logbackin') }}">
                            <i class="fas fa-sign-in-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            {{ __('Log back in') }}
                        </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" href="#" data-toggle="modal"
                                data-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->
    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-open sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('home') }}" class="brand-link">
            <img width="64" height="64"
                 src="{{ \Illuminate\Support\Facades\Storage::disk('public')->exists('icon.png') ? asset('storage/icon.png') : asset('images/controlpanel_logo.png') }}"
                 alt="{{ config('app.name', 'Laravel') }} Logo" class="brand-image img-circle"
                 style="opacity: .8">
            <span class="brand-text font-weight-light">{{ config('app.name', 'Controlpanel.gg') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar" style="overflow-y: auto">

            <!-- Sidebar Menu -->
            <nav class="my-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                    <li class="nav-item">
                        <a href="{{ route('home') }}"
                           class="nav-link @if (Request::routeIs('home')) active @endif">
                            <i class="nav-icon fa fa-home"></i>
                            <p>{{ __('Dashboard') }}</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('servers.index') }}"
                           class="nav-link @if (Request::routeIs('servers.*')) active @endif">
                            <i class="nav-icon fa fa-server"></i>
                            <p>{{ __('Servers') }}
                                <span class="badge badge-info right">{{ Auth::user()->servers()->count() }} /
                                        {{ Auth::user()->server_limit }}</span>
                            </p>
                        </a>
                    </li>

                    @if (env('APP_ENV') == 'local' ||
                        (config('SETTINGS::PAYMENTS:PAYPAL:SECRET') && config('SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID')) ||
                        (config('SETTINGS::PAYMENTS:STRIPE:SECRET') &&
                            config('SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET') &&
                            config('SETTINGS::PAYMENTS:STRIPE:METHODS')))
                        <li class="nav-item">
                            <a href="{{ route('store.index') }}"
                               class="nav-link @if (Request::routeIs('store.*') || Request::routeIs('checkout')) active @endif">
                                <i class="nav-icon fa fa-coins"></i>
                                <p>{{ __('Store') }}</p>
                            </a>
                        </li>
                    @endif
                    @if (config('SETTINGS::TICKET:ENABLED'))
                        <li class="nav-item">
                            <a href="{{ route('ticket.index') }}"
                               class="nav-link @if (Request::routeIs('ticket.*')) active @endif">
                                <i class="nav-icon fas fas fa-ticket-alt"></i>
                                <p>{{ __('Support Ticket') }}</p>
                            </a>
                        </li>
                    @endif

                    @if ((Auth::user()->role == 'admin' || Auth::user()->role == 'moderator') && config('SETTINGS::TICKET:ENABLED'))
                        <li class="nav-header">{{ __('Moderation') }}</li>

                        <li class="nav-item">
                            <a href="{{ route('moderator.ticket.index') }}"
                               class="nav-link @if (Request::routeIs('moderator.ticket.index')) active @endif">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>{{ __('Ticket List') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('moderator.ticket.blacklist') }}"
                               class="nav-link @if (Request::routeIs('moderator.ticket.blacklist')) active @endif">
                                <i class="nav-icon fas fa-user-times"></i>
                                <p>{{ __('Ticket Blacklist') }}</p>
                            </a>
                        </li>
                    @endif

                    @if (Auth::user()->role == 'admin')
                        <li class="nav-header">{{ __('Administration') }}</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.overview.index') }}"
                               class="nav-link @if (Request::routeIs('admin.overview.*')) active @endif">
                                <i class="nav-icon fa fa-home"></i>
                                <p>{{ __('Overview') }}</p>
                            </a>
                        </li>


                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}"
                               class="nav-link @if (Request::routeIs('admin.settings.*')) active @endif">
                                <i class="nav-icon fas fa-tools"></i>
                                <p>{{ __('Settings') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.api.index') }}"
                               class="nav-link @if (Request::routeIs('admin.api.*')) active @endif">
                                <i class="nav-icon fa fa-gamepad"></i>
                                <p>{{ __('Application API') }}</p>
                            </a>
                        </li>

                        <li class="nav-header">{{ __('Management') }}</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}"
                               class="nav-link @if (Request::routeIs('admin.users.*')) active @endif">
                                <i class="nav-icon fas fa-users"></i>
                                <p>{{ __('Users') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.servers.index') }}"
                               class="nav-link @if (Request::routeIs('admin.servers.*')) active @endif">
                                <i class="nav-icon fas fa-server"></i>
                                <p>{{ __('Servers') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.products.index') }}"
                               class="nav-link @if (Request::routeIs('admin.products.*')) active @endif">
                                <i class="nav-icon fas fa-sliders-h"></i>
                                <p>{{ __('Products') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.store.index') }}"
                               class="nav-link @if (Request::routeIs('admin.store.*')) active @endif">
                                <i class="nav-icon fas fa-shopping-basket"></i>
                                <p>{{ __('Store') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.vouchers.index') }}"
                               class="nav-link @if (Request::routeIs('admin.vouchers.*')) active @endif">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p>{{ __('Vouchers') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.partners.index') }}"
                               class="nav-link @if (Request::routeIs('admin.partners.*')) active @endif">
                                <i class="nav-icon fas fa-handshake"></i>
                                <p>{{ __('Partners') }}</p>
                            </a>
                        </li>

                        {{-- <li class="nav-header">Pterodactyl</li> --}}

                        {{-- <li class="nav-item"> --}}
                        {{-- <a href="{{route('admin.nodes.index')}}" --}}
                        {{-- class="nav-link @if (Request::routeIs('admin.nodes.*')) active @endif"> --}}
                        {{-- <i class="nav-icon fas fa-sitemap"></i> --}}
                        {{-- <p>Nodes</p> --}}
                        {{-- </a> --}}
                        {{-- </li> --}}

                        {{-- <li class="nav-item"> --}}
                        {{-- <a href="{{route('admin.nests.index')}}" --}}
                        {{-- class="nav-link @if (Request::routeIs('admin.nests.*')) active @endif"> --}}
                        {{-- <i class="nav-icon fas fa-th-large"></i> --}}
                        {{-- <p>Nests</p> --}}
                        {{-- </a> --}}
                        {{-- </li> --}}


                        <li class="nav-header">{{ __('Other') }}</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.usefullinks.index') }}"
                               class="nav-link @if (Request::routeIs('admin.usefullinks.*')) active @endif">
                                <i class="nav-icon fas fa-link"></i>
                                <p>{{ __('Useful Links') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.legal.index') }}"
                               class="nav-link @if (Request::routeIs('admin.legal.*')) active @endif">
                                <i class="nav-icon fas fa-link"></i>
                                <p>{{ __('Legal Sites') }}</p>
                            </a>
                        </li>

                        <li class="nav-header">{{ __('Logs') }}</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.payments.index') }}"
                               class="nav-link @if (Request::routeIs('admin.payments.*')) active @endif">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>{{ __('Payments') }}
                                    <span
                                        class="badge badge-success right">{{ \App\Models\Payment::count() }}</span>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.activitylogs.index') }}"
                               class="nav-link @if (Request::routeIs('admin.activitylogs.*')) active @endif">
                                <i class="nav-icon fas fa-clipboard-list"></i>
                                <p>{{ __('Activity Logs') }}</p>
                            </a>
                        </li>
                    @endif

                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->

    <div class="content-wrapper">

        @if (!Auth::user()->hasVerifiedEmail())
            @if (Auth::user()->created_at->diffInHours(now(), false) > 1)
                <div class="alert alert-warning p-2 m-2">
                    <h5><i class="icon fas fa-exclamation-circle"></i> {{ __('Warning!') }}</h5>
                    {{ __('You have not yet verified your email address') }} <a class="text-primary"
                                                                                href="{{ route('verification.send') }}">{{ __('Click here to resend verification email') }}</a>
                    <br>
                    {{ __('Please contact support If you didnt receive your verification email.') }}
                </div>
            @endif
        @endif

        @yield('content')

        @include('models.redeem_voucher_modal')
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2021-{{ date('Y') }} <a
                href="{{ url('/') }}">{{ env('APP_NAME', 'Laravel') }}</a>.</strong>
        All rights
        reserved. Powered by <a href="https://controlpanel.gg">ControlPanel</a>. | Theme by <a href="https://2icecube.de/cpgg">2IceCube</a>
        @if (!str_contains(config('BRANCHNAME'), 'main') && !str_contains(config('BRANCHNAME'), 'unknown'))
            Version <b>{{ config('app')['version'] }} - {{ config('BRANCHNAME') }}</b>
        @endif

        {{-- Show imprint and privacy link --}}
        <div class="float-right d-none d-sm-inline-block">
            @if (config('SETTINGS::SYSTEM:SHOW_IMPRINT') == "true")
                <a target="_blank" href="{{ route('imprint') }}"><strong>{{ __('Imprint') }}</strong></a> |
            @endif
            @if (config('SETTINGS::SYSTEM:SHOW_PRIVACY') == "true")
                <a target="_blank" href="{{ route('privacy') }}"><strong>{{ __('Privacy') }}</strong></a>
            @endif
            @if (config('SETTINGS::SYSTEM:SHOW_TOS') == "true")
                | <a target="_blank" href="{{ route('tos') }}"><strong>{{ __('Terms of Service') }}</strong></a>
            @endif
        </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.14.1/dist/sweetalert2.all.min.js"></script>

<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.24/datatables.min.js"></script>
<!-- Summernote -->
<script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
<!-- select2 -->
<script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>

<!-- Moment.js -->
<script src="{{ asset('plugins/moment/moment.min.js') }}"></script>

<!-- Datetimepicker -->
<script src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

<!-- Select2 -->
<script src={{ asset('plugins/select2/js/select2.min.js') }}></script>


<script>
    $(document).ready(function() {
        $('[data-toggle="popover"]').popover();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
</script>
<script>
    @if (Session::has('error'))
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        html: '{{ Session::get('error') }}',
    })
    @endif
    @if (Session::has('success'))
    Swal.fire({
        icon: 'success',
        title: '{{ Session::get('success') }}',
        position: 'top-end',
        showConfirmButton: false,
        background: '#343a40',
        toast: true,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })
    @endif
    @if (Session::has('info'))
    Swal.fire({
        icon: 'info',
        title: '{{ Session::get('info') }}',
        position: 'top-end',
        showConfirmButton: false,
        background: '#343a40',
        toast: true,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })
    @endif
</script>
</body>

</html>
