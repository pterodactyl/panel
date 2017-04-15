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
        <title>{{ Settings::get('company', 'Pterodactyl') }} - Console &rarr; {{ $server->name }}</title>
        @include('layouts.scripts')
        {!! Theme::css('vendor/terminal/jquery.terminal.css') !!}
    </head>
    <body style="margin:0;width:100%;height:100%;">
        <div id="terminal" style="width:100%"></div>
        <div id="terminalNotify" class="terminal-notify hidden">
            <i class="fa fa-bell"></i>
        </div>
    </body>
    <script>window.SkipConsoleCharts = true</script>
    {!! Theme::js('js/laroute.js') !!}
    {!! Theme::js('vendor/jquery/jquery.min.js') !!}
    {!! Theme::js('vendor/socketio/socket.io.min.js') !!}
    {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js') !!}
    {!! Theme::js('js/frontend/server.socket.js') !!}
    {!! Theme::js('vendor/mousewheel/jquery.mousewheel-min.js') !!}
    {!! Theme::js('vendor/terminal/jquery.terminal.min.js') !!}
    {!! Theme::js('vendor/terminal/unix_formatting.js') !!}
    {!! Theme::js('js/frontend/console.js') !!}
    <script>
        Terminal.resize($(window).innerWidth() - 20, $(window).innerHeight() - 20);
        $(window).on('resize', function () {
            Terminal.resize($(window).innerWidth() - 20, $(window).innerHeight() - 20);
        });
    </script>
</html>
