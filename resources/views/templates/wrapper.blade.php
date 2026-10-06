<!DOCTYPE html>
{{-- Dark is the shipping theme; the React SPA's semantic tokens key off this class (assets/tailwind.css). --}}
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <title>{{ config('app.name', 'Pterodactyl') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="noindex">
            {{-- An extension that registers one of these elements (registerHeadTags)
                 replaces the panel's default instead of adding a second one. --}}
            @unless ($extensionHead->replaces('link', 'apple-touch-icon'))
                <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
            @endunless
            @unless ($extensionHead->replaces('link', 'icon'))
                <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg">
                <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
                <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
            @endunless
            @unless ($extensionHead->replaces('link', 'manifest'))
                <link rel="manifest" href="/favicons/manifest.json">
            @endunless
            @unless ($extensionHead->replaces('link', 'mask-icon'))
                <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#3d7bfd">
            @endunless
            @unless ($extensionHead->replaces('link', 'shortcut icon'))
                <link rel="shortcut icon" href="/favicons/favicon.ico">
            @endunless
            @unless ($extensionHead->replaces('meta', 'msapplication-config'))
                <meta name="msapplication-config" content="/favicons/browserconfig.xml">
            @endunless
            {{-- data-theme-token: the SPA keeps this tag equal to the --theme-color token.
                 A theme-color registered by an extension is left exactly as rendered. --}}
            @unless ($extensionHead->replaces('meta', 'theme-color'))
                <meta name="theme-color" content="#0e4688" data-theme-token>
            @endunless
            {{ $extensionHead }}
        @show

        {{-- @json escapes < > & ' " so a stored value such as "<!--<script" cannot
             change how the browser parses these inline scripts. --}}
        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.PterodactylUser = @json(Auth::user()->toVueObject());
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = @json($siteConfiguration);
                </script>
            @endif
        @show

        @yield('assets')
        {!! $asset->cssImports('resources/scripts/index.tsx') !!}
        {{-- Applied theme (p:theme:apply): token overrides linked after the app CSS so
             the theme's :root/.dark declarations win the cascade at equal specificity. --}}
        @if (file_exists(public_path('assets/theme.css')))
            <link rel="stylesheet" href="/assets/theme.css?v={{ filemtime(public_path('assets/theme.css')) }}">
        @endif
    </head>
    <body class="{{ $css['body'] ?? 'bg-background' }}">
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
        @section('scripts')
            {{-- The import map must precede any module script: it lets runtime-loaded
                 extension bundles share the panel's React/TanStack/SDK module instances. --}}
            {!! $asset->importMap() !!}
            {!! $asset->js('resources/scripts/index.tsx') !!}
        @show
    </body>
</html>
