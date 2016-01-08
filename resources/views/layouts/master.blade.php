<!DOCTYPE html>
<html lang="en">
<head>
    @section('scripts')
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex">
        <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
        <link rel="stylesheet" href="{{ asset('css/pterodactyl.css') }}">
        <link rel="stylesheet" href="{{ asset('css/animate.css') }}">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/socket.io/1.3.7/socket.io.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
        <script src="{{ asset('js/admin.min.js') }}"></script>
        <script src="{{ asset('js/bootstrap-notify.min.js') }}"></script>
        <script>
            $(document).ready(function () {
                $.notifyDefaults({
                    placement: {
                        from: 'bottom',
                        align: 'right'
                    },
                    newest_on_top: true,
                    delay: 2000,
                    animate: {
                        enter: 'animated fadeInUp',
                        exit: 'animated fadeOutDown'
                    }
                });
            });
        </script>
        @section('server-socket')
            @if (isset($server->name) && isset($node->name))
                <script>
                    var socket;
                    var notifySocketError = false;
                    $(window).load(function () {

                        // Main Socket Object
                        socket = io('{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/ws/{{ $server->uuid }}', {
                            'query': 'token={{ $server->daemonSecret }}'
                        });

                        // Socket Failed to Connect
                        socket.io.on('connect_error', function (err) {
                            $('#applyUpdate').removeClass('fa-circle-o-notch fa-spinner fa-spin').addClass('fa-question-circle').css({ color: '#FF9900' });
                            if(typeof notifySocketError !== 'object') {
                                notifySocketError = $.notify({
                                    message: '{!! trans('server.ajax.socket_error') !!}'
                                }, {
                                    type: 'danger',
                                    delay: 0
                                });
                            }
                        });

                        // Connected to Socket Successfully
                        socket.on('connect', function () {
                            if (notifySocketError !== false) {
                                notifySocketError.close();
                                notifySocketError = false;
                            }
                        });

                        socket.on('error', function (err) {
                            console.error('There was an error while attemping to connect to the websocket: ' + err + '\n\nPlease try loading this page again.');
                        });

                        // Socket Sends Server Status on Connect
                        socket.on('initial_status', function (data) {
                            var color = '#E33200';
                            var selector = 'fa-times-circle';

                            if (data.status === 1) {
                                color = '#53B30C';
                                selector = 'fa-check-circle';
                            }

                            $('#applyUpdate').removeClass('fa-circle-o-notch fa-spinner fa-spin fa-check-circle fa-times-circle').addClass(selector).css({ color: color });
                        });

                        // Socket Recieves New Status from Scales
                        socket.on('status', function(data) {
                            if(data.status !== 'crashed') {

                                var newStatus, selector = 'fa-times-circle';
                                var color = '#E33200';

                                switch (data.status) {
                                    case 0:
                                        newStatus = 'OFF';
                                        break;
                                    case 1:
                                        newStatus = 'ON';
                                        color = "#53B30C";
                                        selector = "fa-check-circle";
                                        break;
                                    case 2:
                                        newStatus = 'STARTING';
                                        break;
                                    case 3:
                                        newStatus = 'STOPPING';
                                        break;
                                }

                                $('#applyUpdate').removeClass('fa-circle-o-notch fa-spinner fa-spin fa-check-circle fa-times-circle').addClass(selector).css({ color: color });

                                $.notify({
                                    message: '{{ trans('server.ajax.socket_status') }} <strong>' + newStatus + '</strong>.'
                                }, {
                                    type: 'info'
                                });

                            } else {

                                $.notify({
                                    message: '{{ trans('server.ajax.socket_status_crashed') }}'
                                }, {
                                    delay: 5000,
                                    type: 'danger'
                                });
                            }

                        });

                    });
                </script>
            @endif
        @show
    @show
    <title>Pterodactyl - @yield('title')</title>
</head>
<body>
    <div class="container">
        <div class="navbar navbar-default">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-responsive-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">Pterodactyl</a>
            </div>
            <div class="navbar-collapse collapse navbar-responsive-collapse">
                @section('server-name')
                    @if (isset($server->name) && isset($node->name))
                        <ul class="nav navbar-nav">
                            <li class="active" id="{{ $server->name }}"><a href="/server/{{ $server->id }}/index"><i id="applyUpdate" class="fa fa-circle-o-notch fa-spinner fa-spin spin-light"></i> {{ $server->name }}</a></li>
                        </ul>
                    @endif
                @show
                @section('right-nav')
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">{{ trans('strings.language') }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="/language/de">Deutsch</a></li>
                                <li><a href="/language/en">English</a></li>
                                <li><a href="/language/es">Espa&ntilde;ol</a></li>
                                <li><a href="/language/fr">Fran&ccedil;ais</a></li>
                                <li><a href="/language/it">Italiano</a></li>
                                <li><a href="/language/pl">Polski</a></li>
                                <li><a href="/language/pt">Portugu&ecirc;s</a></li>
                                <li><a href="/language/ru">&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;</a></li>
                                <li><a href="/language/se">Svenska</a></li>
                                <li><a href="/language/zh">&#20013;&#22269;&#30340;的</a></li>
                            </ul>
                        </li>
                        @if (null !== Auth::user() && Auth::user()->root_admin == 1)
                            <li class="hidden-xs"><a href="/admin/"><i class="fa fa-cogs"></i></a></li>
                        @endif
                        <li class="hidden-xs"><a href="/auth/logout"><i class="fa fa-power-off"></i></a></li>

                    </ul>
                @show
            </div>
        </div>
        <!-- Add Back Mobile Support -->
        <div class="row">
            <div class="col-md-3 hidden-xs hidden-sm" id="sidebar_links">
                @section('sidebar')
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-heading"><strong>{{ trans('pagination.sidebar.account_controls') }}</strong></a>
                        <a href="/account" class="list-group-item">{{ trans('pagination.sidebar.account_settings') }}</a>
                        <a href="/account/totp" class="list-group-item">{{ trans('pagination.sidebar.account_security') }}</a>
                        <a href="/" class="list-group-item">{{ trans('pagination.sidebar.servers') }}</a>
                    </div>
                    @section('sidebar-server')
                        @if (isset($server->name) && isset($node->name))
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-heading"><strong>{{ trans('pagination.sidebar.server_controls') }}</strong></a>
                                <a href="/server/{{ $server->uuidShort }}/" class="list-group-item server-index">{{ trans('pagination.sidebar.overview') }}</a>
                                <a href="/server/{{ $server->uuidShort }}/files" class="list-group-item server-files">{{ trans('pagination.sidebar.files') }}</a>
                                <a href="/server/{{ $server->uuidShort }}/users" class="list-group-item server-users">{{ trans('pagination.sidebar.subusers') }}</a>
                                <a href="/server/{{ $server->uuidShort }}/settings" class="list-group-item server-settings">{{ trans('pagination.sidebar.manage') }}</a>
                            </div>
                        @endif
                    @show
                @show
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-12" id="tpl_messages">
                        @if (count($errors) > 0)
                            <div class="alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <strong>{{ trans('strings.whoops') }}!</strong> {{ trans('auth.errorencountered') }}<br><br>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @foreach (Alert::getMessages() as $type => $messages)
                            @foreach ($messages as $message)
                                <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    {{ $message }}
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                <div class="row">
                    @yield('content')
                </div>
            </div>
        </div>
        <div class="footer">
            <div class="row">
                <div class="col-md-12">
                    Copyright &copy; 2012 - {{ date('Y') }} <a href="https://github.com/Pterodactyl-IO/Panel" target="_blank">Pterodactyl Software &amp; Design</a>.<br />
                    Pterodactyl is licensed under a <a href="http://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank">GPLv3</a> license. <!-- Please do not remove this license notice. -->
                </div>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function () {
        // Remeber Active Tab and Navigate to it on Reload
        for(var queryParameters={},queryString=location.search.substring(1),re=/([^&=]+)=([^&]*)/g,m;m=re.exec(queryString);)queryParameters[decodeURIComponent(m[1])]=decodeURIComponent(m[2]);$("a[data-toggle='tab']").click(function(){queryParameters.tab=$(this).attr("href").substring(1),window.history.pushState(null,null,location.pathname+"?"+$.param(queryParameters))});
        if($.urlParam('tab') != null){$('.nav.nav-tabs a[href="#' + $.urlParam('tab') + '"]').tab('show');}
        @if (count($errors) > 0)
            @foreach ($errors->all() as $error)
                <?php preg_match('/^The\s(.*?)\s/', $error, $matches) ?>
                @if (isset($matches[1]))
                    $('[name="{{ $matches[1] }}"]').parent().parent().addClass('has-error');
                @endif
            @endforeach
        @endif
    });
    </script>
</body>
</html>
