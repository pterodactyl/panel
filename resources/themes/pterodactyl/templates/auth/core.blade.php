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
            {!! $asset->css('assets/css/bundle.css') !!}
        @show

        @include('layouts.scripts')
    </head>
    <body class="bg-grey-darkest">
        <div class="container" id="pterodactyl">
            <div class="w-full max-w-xs sm:max-w-sm m-auto mt-8">
                <div class="text-center">
                    <img src="/favicons/android-chrome-512x512.png" class="max-w-xxs">
                </div>
                <router-view></router-view>
                <p class="text-center text-grey text-xs">
                    {{ trans('strings.copyright', ['year' => date('Y')]) }}
                </p>
            </div>
        </div>
        @section('scripts')
            {!! $asset->js('assets/scripts/bundle.js') !!}
        @show
    </body>
</html>
