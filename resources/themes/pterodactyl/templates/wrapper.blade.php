<html>
    <head>
        <title>{{ config('app.name', 'Pterodactyl') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
        @show

        @section('assets')
            {!! $asset->css('main.css') !!}
        @show

        @include('layouts.scripts')
    </head>
    <body class="{{ $css['body'] ?? 'bg-grey-lighter' }}">
        @section('content')
            @yield('above-container')
            <div id="pterodactyl">
                @yield('container')
            </div>
            @yield('below-container')
        @show
        @section('scripts')
            {!! $asset->js('main.js') !!}
        @show
    </body>
</html>
