@extends('templates/wrapper', [
    'css' => ['body' => 'bg-grey-darkest']
])

@section('container')
    <div class="w-full max-w-xs sm:max-w-sm m-auto mt-8">
        <div class="text-center">
            <img src="/favicons/android-chrome-512x512.png" class="max-w-xxs">
        </div>
        <router-view></router-view>
        <p class="text-center text-grey text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
