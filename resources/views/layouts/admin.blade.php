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
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/fuelux/3.13.0/css/fuelux.min.css" />
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/fuelux/3.13.0/js/fuelux.min.js"></script>
        <script src="{{ asset('js/admin.min.js') }}"></script>
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
                <a class="navbar-brand" href="/">Pterodactyl - Laravel</a>
            </div>
            <div class="navbar-collapse collapse navbar-responsive-collapse">
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
                        <li class="hidden-xs"><a href="/"><i class="fa fa-server"></i></a></li>
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
                        <a href="#" class="list-group-item list-group-item-heading"><strong>Management</strong></a>
                        <a href="/admin" id="sidenav_admin-index" class="list-group-item">Admin Index</a>
                        <a href="/admin/settings" class="list-group-item">General Settings</a>
                        <a href="/admin/api" class="list-group-item">API Management</a>
                    </div>
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-heading"><strong>Account Management</strong></a>
                        <a href="/admin/accounts" class="list-group-item">Find Account</a>
                        <a href="/admin/accounts/new" class="list-group-item">New Account</a>
                    </div>
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-heading"><strong>Server Management</strong></a>
                        <a href="/admin/servers" class="list-group-item">Find Server</a>
                        <a href="/admin/servers/new" class="list-group-item">New Server</a>
                    </div>
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-heading"><strong>Node Management</strong></a>
                        <a href="/admin/nodes" class="list-group-item">List Nodes</a>
                        <a href="/admin/locations" class="list-group-item">Manage Locations</a>
                        <a href="/admin/nodes/new" class="list-group-item">Add Node</a>
                    </div>
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-heading"><strong>Service Management</strong></a>
                        <a href="/admin/services" class="list-group-item">List Services</a>
                        <a href="/admin/services/new" class="list-group-item">Add Service</a>
                    </div>
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
                                    {!! $message !!}
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
