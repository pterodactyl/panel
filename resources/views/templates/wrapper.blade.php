<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ config('app.name', 'ClaqNode Hosting') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="noindex">
            <link rel="apple-touch-icon" sizes="180x180" href="https://cdn.discordapp.com/attachments/1179761313103220796/1180393394606977075/image-191x191.jpg">
            <link rel="icon" type="image/png" href="https://cdn.discordapp.com/attachments/1179761313103220796/1180393394606977075/image-191x191.jpg" sizes="32x32">
            <link rel="icon" type="image/png" href="https://cdn.discordapp.com/attachments/1179761313103220796/1180393394606977075/image-191x191.jpg" sizes="16x16">
            <link rel="manifest" href="/favicons/manifest.json">
            <link rel="mask-icon" href="https://cdn.discordapp.com/attachments/1179761313103220796/1180393394606977075/image-191x191.jpg" color="#bc6e3c">
            <link rel="shortcut icon" href="https://cdn.discordapp.com/attachments/1179761313103220796/1180393394606977075/image-191x191.jpg">
            <meta name="msapplication-config" content="/favicons/browserconfig.xml">
            <meta name="theme-color" content="#1f8b4c">
        @show

        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.PterodactylUser = {!! json_encode(Auth::user()->toReactObject()) !!};
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
                </script>
            @endif
        @show
        <style>
            @import url('//fonts.googleapis.com/css?family=Rubik:300,400,500&display=swap');
            @import url('//fonts.googleapis.com/css?family=IBM+Plex+Mono|IBM+Plex+Sans:500&display=swap');
        </style>

        @yield('assets')

        @include('layouts.scripts')

        @viteReactRefresh
        @vite('resources/scripts/index.tsx')
    </head>
    <body class="{{ $css['body'] ?? 'bg-neutral-50' }}">
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
    </body>
</html>
