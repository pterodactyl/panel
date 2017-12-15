{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
<!DOCTYPE html>
<html>
    <head>
        <title>{{ config('app.name', 'Pterodactyl') }} - Console &rarr; {{ $server->name }}</title>
        @include('layouts.scripts')
        {!! Theme::css('vendor/bootstrap/bootstrap.min.css') !!}
        {!! Theme::css('css/terminal.css') !!}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    <body id="terminal-body">
        <div id="terminal" style="width:100%;max-height: none !important;"></div>
        <div id="terminal_input" class="form-group no-margin">
            <div class="input-group">
                <div class="input-group-addon terminal_input--prompt">container:~/$</div>
                <input type="text" class="form-control terminal_input--input">
            </div>
        </div>
        <div id="terminalNotify" class="terminal-notify hidden">
            <i class="fa fa-bell"></i>
        </div>
    </body>
    <script>window.SkipConsoleCharts = true</script>
    {!! Theme::js('js/laroute.js') !!}
    {!! Theme::js('vendor/ansi/ansi_up.js') !!}
    {!! Theme::js('vendor/jquery/jquery.min.js') !!}
    {!! Theme::js('vendor/socketio/socket.io.v203.min.js') !!}
    {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js') !!}
    {!! Theme::js('js/frontend/server.socket.js') !!}
    {!! Theme::js('vendor/mousewheel/jquery.mousewheel-min.js') !!}
    {!! Theme::js('js/frontend/console.js') !!}
    <script>
        $terminal.height($(window).innerHeight() - 40);
        $terminal.width($(window).innerWidth());
        $(window).on('resize', function () {
            window.scrollToBottom();
            $terminal.height($(window).innerHeight() - 40);
            $terminal.width($(window).innerWidth());
        });
    </script>
</html>
